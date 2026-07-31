<?php

namespace App\Http\Controllers\Documentation;

use App\Enums\DocumentationStatus;
use App\Http\Controllers\Controller;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('documentation/Index', [
            'categories' => DocumentationCategory::query()
                ->whereHas('documentations', fn ($query) => $query->where('status', DocumentationStatus::Published))
                ->orderBy('position')
                ->with(['documentations' => fn ($query) => $query->where('status', DocumentationStatus::Published)->orderBy('position')])
                ->get(),
        ]);
    }

    public function show(Documentation $documentation): Response
    {
        abort_unless($documentation->status === DocumentationStatus::Published, 404);

        return Inertia::render('documentation/Show', [
            'documentation' => $documentation->load('category'),
            'categories' => DocumentationCategory::query()
                ->whereHas('documentations', fn ($query) => $query->where('status', DocumentationStatus::Published))
                ->orderBy('position')
                ->with(['documentations' => fn ($query) => $query->where('status', DocumentationStatus::Published)->orderBy('position')])
                ->get(),
        ]);
    }
}
