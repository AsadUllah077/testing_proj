<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /**
     * @var GlobalSearchService
     */
    protected $searchService;

    /**
     * GlobalSearchController constructor.
     *
     * @param GlobalSearchService $searchService
     */
    public function __construct(GlobalSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Perform the global search.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('query', '');

        $results = $this->searchService->search($query);

        return response()->json($results);
    }
}
