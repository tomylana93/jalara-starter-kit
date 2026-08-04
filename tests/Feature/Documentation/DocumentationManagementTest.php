<?php

use App\Enums\DocumentationStatus;
use App\Enums\Role;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * @return array<string, mixed>
 */
function documentationContent(string $text = 'Reset the password'): array
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
            'title' => 'Account guide',
            'slug' => '',
            'status' => DocumentationStatus::Published->value,
            'content' => documentationContent('How to change the profile'),
        ])
        ->assertRedirect(route('documentation.manage.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'The documentation has been created.']);

    $documentation = Documentation::query()->sole();
    expect($documentation->slug)->toBe('account-guide')
        ->and($documentation->searchable_text)->toBe('How to change the profile')
        ->and($documentation->published_at)->not->toBeNull();
});

it('regenerates the slug of a draft submitted without one', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $documentation = Documentation::factory()->create(['slug' => 'old-title', 'published_at' => null]);

    $this->actingAs($admin)
        ->put(route('documentation.manage.documents.update', $documentation), [
            'documentation_category_id' => $documentation->documentation_category_id,
            'title' => 'Fresh title',
            'slug' => '',
            'status' => DocumentationStatus::Draft->value,
            'content' => documentationContent('Document body'),
        ])
        ->assertRedirect(route('documentation.manage.index'));

    expect($documentation->refresh()->slug)->toBe('fresh-title');
});

it('rejects a custom slug that normalizes onto an existing one', function () {
    $admin = userWithRole(Role::SuperAdmin);
    Documentation::factory()->create(['slug' => 'reset-password']);
    $category = DocumentationCategory::factory()->create();

    $this->actingAs($admin)
        ->post(route('documentation.manage.documents.store'), [
            'documentation_category_id' => $category->id,
            'title' => 'Account guide',
            'slug' => 'Reset_Password',
            'status' => DocumentationStatus::Draft->value,
            'content' => documentationContent('Document body'),
        ])
        ->assertSessionHasErrors('slug');
});

it('rejects a custom slug that normalizes to nothing', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $category = DocumentationCategory::factory()->create();

    $this->actingAs($admin)
        ->post(route('documentation.manage.documents.store'), [
            'documentation_category_id' => $category->id,
            'title' => 'Account guide',
            'slug' => '!!!',
            'status' => DocumentationStatus::Draft->value,
            'content' => documentationContent('Document body'),
        ])
        ->assertSessionHasErrors('slug');

    expect(Documentation::query()->count())->toBe(0);
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
        ->assertRedirect(route('documentation.manage.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'The documentation has been deleted.']);

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
            ->where('documentations.data.0.title', 'First category, first document')
            ->where('documentations.data.1.title', 'First category, second document')
            ->where('documentations.data.2.title', 'Second category, first document'));
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

it('returns the author to the management list with a toast after an update', function () {
    $documentation = Documentation::factory()->create(['title' => 'Old title']);

    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->put(route('documentation.manage.documents.update', $documentation), [
            'documentation_category_id' => $documentation->documentation_category_id,
            'title' => 'New title',
            'slug' => (string) $documentation->slug,
            'status' => DocumentationStatus::Draft->value,
            'content' => documentationContent('Updated body'),
        ])
        ->assertRedirect(route('documentation.manage.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'The documentation has been updated.']);

    expect($documentation->refresh()->title)->toBe('New title');
});

it('keeps a rejected submission on the editor instead of redirecting to the list', function () {
    $documentation = Documentation::factory()->create(['title' => 'Old title']);
    $editor = route('documentation.manage.documents.edit', $documentation);

    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->from($editor)
        ->put(route('documentation.manage.documents.update', $documentation), [
            'documentation_category_id' => $documentation->documentation_category_id,
            'title' => '',
            'slug' => (string) $documentation->slug,
            'status' => DocumentationStatus::Draft->value,
            'content' => documentationContent(),
        ])
        ->assertRedirect($editor)
        ->assertSessionHasErrors('title');

    expect($documentation->refresh()->title)->toBe('Old title');
});

it('reports every category mutation with a toast but keeps reordering silent', function () {
    $admin = userWithRole(Role::SuperAdmin);

    $this->actingAs($admin)
        ->post(route('documentation.manage.categories.store'), ['name' => 'Account'])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'The category has been created.']);

    $category = DocumentationCategory::query()->sole();

    $this->actingAs($admin)
        ->put(route('documentation.manage.categories.update', $category), ['name' => 'Account & security'])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'The category has been updated.']);

    $this->actingAs($admin)
        ->post(route('documentation.manage.categories.move', [$category, 'up']))
        ->assertSessionMissing('toast');

    $this->actingAs($admin)
        ->delete(route('documentation.manage.categories.destroy', $category))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'The category has been deleted.']);

    expect(DocumentationCategory::query()->count())->toBe(0);
});

it('appends a new category to the end of the manual ordering', function () {
    DocumentationCategory::factory()->create(['position' => 4]);

    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->post(route('documentation.manage.categories.store'), ['name' => 'Terakhir']);

    expect(DocumentationCategory::query()->where('name', 'Terakhir')->sole()->position)->toBe(5);
});

it('swaps a document with its neighbour inside the same category only', function () {
    $category = DocumentationCategory::factory()->create();
    $other = DocumentationCategory::factory()->create();
    $first = Documentation::factory()->create(['documentation_category_id' => $category->id, 'position' => 1]);
    $second = Documentation::factory()->create(['documentation_category_id' => $category->id, 'position' => 2]);
    $elsewhere = Documentation::factory()->create(['documentation_category_id' => $other->id, 'position' => 1]);

    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->post(route('documentation.manage.documents.move', [$second, 'up']))
        ->assertSessionMissing('toast');

    expect($first->refresh()->position)->toBe(2)
        ->and($second->refresh()->position)->toBe(1)
        ->and($elsewhere->refresh()->position)->toBe(1);
});

it('paginates the management list ten rows at a time', function () {
    $category = DocumentationCategory::factory()->create(['position' => 1]);
    Documentation::factory()->count(12)->sequence(fn ($sequence) => [
        'documentation_category_id' => $category->id,
        'position' => $sequence->index + 1,
        'title' => 'Document '.str_pad((string) ($sequence->index + 1), 2, '0', STR_PAD_LEFT),
    ])->create();

    $admin = userWithRole(Role::SuperAdmin);

    $this->actingAs($admin)
        ->get(route('documentation.manage.index'))
        ->assertInertia(fn ($page) => $page
            ->has('documentations.data', 10)
            ->where('documentations.meta.page', 1)
            ->where('documentations.meta.perPage', 10)
            ->where('documentations.meta.total', 12)
            ->where('documentations.meta.lastPage', 2)
            ->where('documentations.data.0.title', 'Document 01'));

    $this->actingAs($admin)
        ->get(route('documentation.manage.index', ['page' => 2]))
        ->assertInertia(fn ($page) => $page
            ->has('documentations.data', 2)
            ->where('documentations.meta.page', 2)
            ->where('documentations.data.0.title', 'Document 11'));

    /* A page past the end settles on the last page that exists. */
    $this->actingAs($admin)
        ->get(route('documentation.manage.index', ['page' => 99]))
        ->assertInertia(fn ($page) => $page->where('documentations.meta.page', 2));
});

it('sends explicit payloads instead of raw documentation models', function () {
    $documentation = Documentation::factory()->published()->create();

    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->get(route('documentation.manage.index'))
        ->assertInertia(fn ($page) => $page
            ->has('documentations.data.0', fn ($row) => $row
                ->hasAll(['id', 'title', 'slug', 'status', 'category'])
                ->has('category', fn ($cat) => $cat
                    ->hasAll(['id', 'name'])
                )
            )
            ->has('categories.0', fn ($category) => $category
                ->hasAll(['id', 'name', 'position', 'documentations_count'])));

    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->get(route('documentation.manage.documents.edit', $documentation))
        ->assertInertia(fn ($page) => $page
            ->has('documentation', fn ($value) => $value
                ->hasAll(['id', 'documentation_category_id', 'title', 'slug', 'status', 'published_at', 'content']))
            ->has('categories.0', fn ($category) => $category
                ->hasAll(['id', 'name']))
            ->where('statuses', [
                ['label' => 'Draft', 'value' => 'draft'],
                ['label' => 'Published', 'value' => 'published'],
            ]));
});

it('denies the management pages to a super administrator the policy refuses', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $documentation = Documentation::factory()->create();

    Gate::before(fn (): bool => false);

    $this->actingAs($admin)->get(route('documentation.manage.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('documentation.manage.create'))->assertForbidden();
    $this->actingAs($admin)->get(route('documentation.manage.documents.edit', $documentation))->assertForbidden();
});

it('does not run the categories query when only documentations are requested', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $category = DocumentationCategory::factory()->create();
    Documentation::factory()->create(['documentation_category_id' => $category->id]);

    DB::enableQueryLog();

    $this->actingAs($admin)
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'documentation/manage/Index',
            'X-Inertia-Partial-Data' => 'documentations',
        ])
        ->get(route('documentation.manage.index'))
        ->assertInertia(fn ($page) => $page
            ->has('documentations')
            ->missing('categories')
        );

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $hasCategoryQuery = false;
    foreach ($queries as $query) {
        if (str_contains($query['query'], 'documentations_count')) {
            $hasCategoryQuery = true;
            break;
        }
    }

    expect($hasCategoryQuery)->toBeFalse();
});

it('runs both queries and returns both props on standard visit', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $category = DocumentationCategory::factory()->create();
    Documentation::factory()->create(['documentation_category_id' => $category->id]);

    DB::enableQueryLog();

    $this->actingAs($admin)
        ->get(route('documentation.manage.index'))
        ->assertInertia(fn ($page) => $page
            ->has('documentations')
            ->has('categories')
        );

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $hasCategoryQuery = false;
    foreach ($queries as $query) {
        if (str_contains($query['query'], 'documentations_count')) {
            $hasCategoryQuery = true;
            break;
        }
    }

    expect($hasCategoryQuery)->toBeTrue();
});

it('allocates automatic sequential collision suffixes and fills gaps', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $category = DocumentationCategory::factory()->create();

    Documentation::factory()->create(['slug' => 'guide', 'title' => 'Guide']);
    Documentation::factory()->create(['slug' => 'guide-2', 'title' => 'Guide 2']);
    Documentation::factory()->create(['slug' => 'guide-4', 'title' => 'Guide 4']);

    $this->actingAs($admin)
        ->post(route('documentation.manage.documents.store'), [
            'documentation_category_id' => $category->id,
            'title' => 'Guide',
            'slug' => '',
            'status' => DocumentationStatus::Draft->value,
            'content' => documentationContent(),
        ])
        ->assertRedirect(route('documentation.manage.index'));

    $doc = Documentation::query()->where('title', 'Guide')->whereNot('slug', 'guide')->firstOrFail();
    expect($doc->slug)->toBe('guide-3');

    $this->actingAs($admin)
        ->post(route('documentation.manage.documents.store'), [
            'documentation_category_id' => $category->id,
            'title' => 'Guide',
            'slug' => '',
            'status' => DocumentationStatus::Draft->value,
            'content' => documentationContent(),
        ])
        ->assertRedirect(route('documentation.manage.index'));

    expect(Documentation::query()->where('slug', 'guide-5')->exists())->toBeTrue();
});

it('uses fallback when title is completely un-sluggable and slug is empty', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $category = DocumentationCategory::factory()->create();

    $this->actingAs($admin)
        ->post(route('documentation.manage.documents.store'), [
            'documentation_category_id' => $category->id,
            'title' => '!!!',
            'slug' => '',
            'status' => DocumentationStatus::Draft->value,
            'content' => documentationContent(),
        ])
        ->assertRedirect(route('documentation.manage.index'));

    $doc = Documentation::query()->where('title', '!!!')->firstOrFail();
    expect($doc->slug)->toBe('documentation');
});

it('excludes the current draft from its own collision checks when updating', function () {
    $admin = userWithRole(Role::SuperAdmin);
    $documentation = Documentation::factory()->create(['slug' => 'guide', 'title' => 'Guide', 'published_at' => null]);

    $this->actingAs($admin)
        ->put(route('documentation.manage.documents.update', $documentation), [
            'documentation_category_id' => $documentation->documentation_category_id,
            'title' => 'Guide',
            'slug' => '',
            'status' => DocumentationStatus::Draft->value,
            'content' => documentationContent(),
        ])
        ->assertRedirect(route('documentation.manage.index'));

    expect($documentation->refresh()->slug)->toBe('guide');
});
