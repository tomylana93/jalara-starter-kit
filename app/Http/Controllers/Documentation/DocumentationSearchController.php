<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Models\Documentation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentationSearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('query'));

        if (mb_strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $like = '%'.$query.'%';

        $results = Documentation::query()
            ->published()
            ->where(fn ($builder) => $builder->whereLike('title', $like)->orWhereLike('slug', $like)->orWhereLike('searchable_text', $like))
            ->orderByRaw('CASE WHEN title LIKE ? THEN 0 WHEN slug LIKE ? THEN 1 ELSE 2 END', [$like, $like])
            ->limit(8)
            ->with('category:id,name')
            ->get(['id', 'title', 'slug', 'documentation_category_id', 'searchable_text'])
            ->map(fn (Documentation $documentation): array => [
                'id' => $documentation->id,
                'title' => $documentation->title,
                'slug' => $documentation->slug,
                'category' => $documentation->category->name,
                'excerpt' => Str::limit($documentation->searchable_text, 120),
            ]);

        return response()->json(['data' => $results]);
    }
}
