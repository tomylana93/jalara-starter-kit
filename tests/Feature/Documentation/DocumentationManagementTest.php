<?php

use App\Enums\DocumentationStatus;
use App\Enums\Role;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use App\Models\User;

function documentationContent(string $text = 'Reset kata sandi'): array
{
    return ['type' => 'doc', 'content' => [['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => $text]]]]];
}

it('allows only super administrators to manage documentation', function () {
    $category = DocumentationCategory::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('documentation.manage.documents.store'), [
            'documentation_category_id' => $category->id,
            'title' => 'Account guide',
            'status' => DocumentationStatus::Draft->value,
            'content' => documentationContent(),
        ])
        ->assertForbidden();

    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->get(route('documentation.manage.index'))
        ->assertSuccessful();
});

it('stores validated tiptap json with an automatic slug and searchable text', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $category = DocumentationCategory::factory()->create();

    $this->actingAs($admin)
        ->post(route('documentation.manage.documents.store'), [
            'documentation_category_id' => $category->id,
            'title' => 'Panduan Akun',
            'slug' => '',
            'status' => DocumentationStatus::Published->value,
            'content' => documentationContent('Cara mengubah profil'),
        ])
        ->assertRedirect();

    $documentation = Documentation::query()->sole();
    expect($documentation->slug)->toBe('panduan-akun')
        ->and($documentation->searchable_text)->toBe('Cara mengubah profil')
        ->and($documentation->published_at)->not->toBeNull();
});

it('rejects unsafe links and locks a slug after first publication', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $documentation = Documentation::factory()->published()->create(['slug' => 'stable-slug']);

    $unsafe = ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Bad', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]]]]]];
    $this->actingAs($admin)
        ->put(route('documentation.manage.documents.update', $documentation), [
            'documentation_category_id' => $documentation->documentation_category_id,
            'title' => $documentation->title,
            'slug' => 'changed-slug',
            'status' => DocumentationStatus::Published->value,
            'content' => $unsafe,
        ])
        ->assertSessionHasErrors(['slug', 'content']);
});

it('blocks deleting a used category and permanently deletes a document', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $documentation = Documentation::factory()->create();
    $category = $documentation->category;

    $this->actingAs($admin)
        ->delete(route('documentation.manage.categories.destroy', $category))
        ->assertSessionHasErrors('category');

    $this->actingAs($admin)
        ->delete(route('documentation.manage.documents.destroy', $documentation))
        ->assertRedirect(route('documentation.manage.index'));

    expect(Documentation::query()->whereKey($documentation->id)->exists())->toBeFalse();
});

it('lists documents by category position and then document position', function () {
    $first = DocumentationCategory::factory()->create(['position' => 1]);
    $second = DocumentationCategory::factory()->create(['position' => 2]);
    Documentation::factory()->create(['documentation_category_id' => $second->id, 'position' => 1, 'title' => 'Second category, first document']);
    Documentation::factory()->create(['documentation_category_id' => $first->id, 'position' => 2, 'title' => 'First category, second document']);
    Documentation::factory()->create(['documentation_category_id' => $first->id, 'position' => 1, 'title' => 'First category, first document']);

    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->get(route('documentation.manage.index'))
        ->assertInertia(fn ($page) => $page
            ->where('documentations.0.title', 'First category, first document')
            ->where('documentations.1.title', 'First category, second document')
            ->where('documentations.2.title', 'Second category, first document'));
});

it('accepts internal paths and HTTP(S) links but rejects protocol-relative ones', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $category = DocumentationCategory::factory()->create();

    $linked = fn (string $href): array => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Link', 'marks' => [['type' => 'link', 'attrs' => ['href' => $href]]]]]]]];

    foreach (['/internal-guide', 'http://example.test/guide', 'https://example.test/guide'] as $index => $href) {
        $this->actingAs($admin)
            ->post(route('documentation.manage.documents.store'), [
                'documentation_category_id' => $category->id,
                'title' => 'Accepted link '.$index,
                'status' => DocumentationStatus::Draft->value,
                'content' => $linked($href),
            ])
            ->assertSessionHasNoErrors();
    }

    $this->actingAs($admin)
        ->post(route('documentation.manage.documents.store'), [
            'documentation_category_id' => $category->id,
            'title' => 'Rejected link',
            'status' => DocumentationStatus::Draft->value,
            'content' => $linked('//external.example/guide'),
        ])
        ->assertSessionHasErrors('content');
});

it('rejects malformed nodes whose marks or content are not arrays', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $category = DocumentationCategory::factory()->create();

    foreach ([['marks' => 'oops'], ['content' => 'oops']] as $index => $malformed) {
        $this->actingAs($admin)
            ->post(route('documentation.manage.documents.store'), [
                'documentation_category_id' => $category->id,
                'title' => 'Malformed '.$index,
                'status' => DocumentationStatus::Draft->value,
                'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', ...$malformed]]],
            ])
            ->assertSessionHasErrors('content');
    }
});
