<?php

namespace App\Http\Controllers\Documentation;

use App\Enums\DocumentationStatus;
use App\Http\Controllers\Controller;
use App\Http\Presenters\DocumentationPresenter;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    /**
     * Show the reader index: published documentation grouped by category.
     */
    public function index(): Response
    {
        return Inertia::render('documentation/Index', [
            'categories' => DocumentationPresenter::readerCategories(
                DocumentationCategory::query()
                    ->whereHas('documentations', fn ($query) => $query->where('status', DocumentationStatus::Published))
                    ->orderBy('position')
                    ->with(['documentations' => fn ($query) => $query->where('status', DocumentationStatus::Published)->orderBy('position')])
                    ->get(),
            ),
        ]);
    }

    /**
     * Show one published document alongside the reader navigation.
     */
    public function show(Documentation $documentation): Response
    {
        abort_unless($documentation->status === DocumentationStatus::Published, 404);

        return Inertia::render('documentation/Show', [
            'documentation' => DocumentationPresenter::readerDetail($documentation->load('category')),
            'categories' => DocumentationPresenter::readerCategories(
                DocumentationCategory::query()
                    ->whereHas('documentations', fn ($query) => $query->where('status', DocumentationStatus::Published))
                    ->orderBy('position')
                    ->with(['documentations' => fn ($query) => $query->where('status', DocumentationStatus::Published)->orderBy('position')])
                    ->get(),
            ),
        ]);
    }
}
