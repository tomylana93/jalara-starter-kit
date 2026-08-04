<?php

use App\Models\Documentation;
use App\Models\DocumentationCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows only published documentation inside the authenticated app', function () {
    $user = User::factory()->create();
    $category = DocumentationCategory::factory()->create(['position' => 1]);
    $published = Documentation::factory()->for($category, 'category')->published()->create(['title' => 'Published guide', 'position' => 1]);
    Documentation::factory()->for($category, 'category')->create(['title' => 'Private draft', 'position' => 2]);

    actingAs($user)
        ->get(route('documentation.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documentation/Index')
            ->has('categories', 1)
            ->where('categories.0.documentations.0.id', $published->id)
            ->missing('categories.0.documentations.1'));
});

it('returns published content and hides drafts', function () {
    $user = User::factory()->create();
    $published = Documentation::factory()->published()->create();
    $draft = Documentation::factory()->create();

    actingAs($user)->get(route('documentation.show', $published))->assertSuccessful();
    actingAs($user)->get(route('documentation.show', $draft))->assertNotFound();
});

it('keeps documentation behind authentication', function () {
    get(route('documentation.index'))->assertRedirect(route('login'));
});
