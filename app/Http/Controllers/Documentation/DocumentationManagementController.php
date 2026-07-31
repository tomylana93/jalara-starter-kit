<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationManagementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('documentation/manage/Index', [
            'categories' => DocumentationCategory::query()->withCount('documentations')->orderBy('position')->get(),
            'documentations' => Documentation::query()
                ->with('category')
                ->orderBy(DocumentationCategory::query()
                    ->select('position')
                    ->whereColumn('documentation_categories.id', 'documentations.documentation_category_id'))
                ->orderBy('position')
                ->get(),
        ]);
    }
}
