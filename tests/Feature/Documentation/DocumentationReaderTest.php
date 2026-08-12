<?php

use App\Models\Documentation;
use App\Models\DocumentationCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

it('leaves the document body out of the reader navigation', function () {
    $user = User::factory()->create();
    $category = DocumentationCategory::factory()->create(['position' => 1]);
    $published = Documentation::factory()->for($category, 'category')->published()->create(['position' => 1]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    actingAs($user)
        ->get(route('documentation.show', $published))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('documentation.content')
            ->where('categories.0.documentations.0.id', $published->id)
            ->missing('categories.0.documentations.0.content'));

    /*
     * The navigation loads the documents of every category, so it is the query
     * that would carry the bodies of the whole manual into one response.
     */
    $navigation = array_filter(
        DB::getQueryLog(),
        fn (array $query): bool => str_contains(
            (string) $query['query'],
            'from "documentations" where "documentations"."documentation_category_id"',
        ),
    );

    DB::disableQueryLog();

    expect($navigation)->not->toBeEmpty();

    $selected = implode(' ', array_column($navigation, 'query'));

    expect($selected)->toContain('"title"')
        ->not->toContain('"content"')
        ->not->toContain('"searchable_text"');
});

it('keeps documentation behind authentication', function () {
    get(route('documentation.index'))->assertRedirect(route('login'));
});
