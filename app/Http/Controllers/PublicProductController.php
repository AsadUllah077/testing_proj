<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    public function show($uniqueId)
    {
        $product = Product::with(['category', 'warehouseLocation', 'disposition', 'workFlowStage'])
            ->where('unique_id', $uniqueId)
            ->firstOrFail();

        return view('pages.products.public.show', compact('product'));
    }

    public function getCountByTitle(Request $request)
    {
        $title = $request->get('title');

        if (! $title) {
            return response()->json([
                'success' => false,
                'message' => 'Title parameter is required',
            ], 400);
        }

        // Count products with matching title (case-insensitive)
        $count = Product::whereRaw('LOWER(title) = ?', [strtolower($title)])->count();

        return response()->json([
            'success' => true,
            'title' => $title,
            'count' => $count,
        ]);
    }

    // public function getCountByTitle3(Request $request)
    // {
    //     $title = trim((string) ($request->query('title') ?? $request->query('q') ?? ''));

    //     if ($title === '') {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'The title or q parameter is required.',
    //         ], 400);
    //     }

    //     preg_match_all('/[\p{L}\p{N}]+/u', $title, $matches);
    //     $words = collect($matches[0] ?? [])
    //         ->map(fn ($word) => mb_strtolower($word))
    //         ->filter()
    //         ->values();

    //     if ($words->count() < 3) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Please provide at least 3 words in the product title.',
    //             'minimum_words' => 3,
    //             'word_count' => $words->count(),
    //         ], 422);
    //     }

    //     $products = Product::with([
    //         'category:id,name',
    //         'warehouseLocation:id,name',
    //         'disposition:id,name',
    //         'workflowStage:id,name',
    //     ])
    //         ->where(function ($query) use ($words) {
    //             foreach ($words->unique() as $word) {
    //                 $query->whereRaw('LOWER(title) LIKE ?', ['%'.$word.'%']);
    //             }
    //         })
    //         ->orderBy('title')
    //         ->orderBy('unique_id')
    //         ->get()
    //         ->map(function ($product) {
    //             return [
    //                 'id' => $product->id,
    //                 'unique_id' => $product->unique_id,
    //                 'title' => $product->title,
    //                 'status' => $product->status,
    //                 'category' => $product->category?->name,
    //                 'warehouse_location' => $product->warehouseLocation?->name,
    //                 'disposition' => $product->disposition?->name,
    //                 'workflow_stage' => $product->workflowStage?->name,
    //                 'specifications' => $product->specifications ?? [],
    //                 'sold' => (bool) $product->sold_status,
    //                 'scrapped' => (bool) $product->scrap_status,
    //                 'manual_product' => (bool) $product->manual_status,
    //                 'units' => $product->manual_status ? (int) ($product->count ?? 0) : 1,
    //             ];
    //         });

    //     return response()->json([
    //         'success' => true,
    //         'query' => $title,
    //         'word_count' => $words->count(),
    //         'match_type' => 'all_words_case_insensitive',
    //         'count' => $products->count(),
    //         'products' => $products,
    //     ]);
    // }
    public function getCountByTitle3(Request $request)
    {
        $title = trim((string) ($request->query('title') ?? $request->query('q') ?? ''));

        if ($title === '') {
            return response()->json([
                'success' => false,
                'message' => 'The title or q parameter is required.',
            ], 400);
        }

        preg_match_all('/[\p{L}\p{N}]+/u', $title, $matches);

        $words = collect($matches[0] ?? [])
            ->map(fn ($word) => mb_strtolower($word))
            ->filter()
            ->unique()
            ->values();

        if ($words->count() < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide at least 3 words in the product title.',
                'minimum_words' => 3,
                'word_count' => $words->count(),
            ], 422);
        }

        $query = Product::with([
            'category:id,name',
            'warehouseLocation:id,name',
            'disposition:id,name',
            'workflowStage:id,name',
        ]);

        // Build a score that counts how many words match
        $scoreSql = [];
        $bindings = [];

        foreach ($words as $word) {
            $scoreSql[] = 'CASE WHEN LOWER(title) LIKE ? THEN 1 ELSE 0 END';
            $bindings[] = "%{$word}%";
        }

        // Require at least 3 matching words
        $query->whereRaw(
            '('.implode(' + ', $scoreSql).') >= 3',
            $bindings
        );

        $products = $query
            ->orderBy('title')
            ->orderBy('unique_id')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'unique_id' => $product->unique_id,
                    'title' => $product->title,
                    'status' => $product->status,
                    'updated_at' => $product->updated_at,
                    'category' => $product->category?->name,
                    'warehouse_location' => $product->warehouseLocation?->name,
                    'disposition' => $product->disposition?->name,
                    'workflow_stage' => $product->workflowStage?->name,
                    'specifications' => $product->specifications ?? [],
                    'sold' => (bool) $product->sold_status,
                    'scrapped' => (bool) $product->scrap_status,
                    'manual_product' => (bool) $product->manual_status,
                    'units' => $product->manual_status
                        ? (int) ($product->count ?? 0)
                        : 1,
                ];
            });

        return response()->json([
            'success' => true,
            'query' => $title,
            'word_count' => $words->count(),
            'minimum_match_required' => 3,
            'count' => $products->count(),
            'products' => $products,
        ]);
    }

    public function getReadyForSaleByTitle(Request $request)
    {
        $title = trim((string) ($request->query('title') ?? $request->query('q') ?? ''));

        if ($title === '') {
            return response()->json([
                'success' => false,
                'message' => 'Title parameter is required',
            ], 400);
        }

        $products = Product::with([
            'category:id,name',
            'warehouseLocation:id,name',
        ])
            ->whereRaw('LOWER(title) = ?', [strtolower($title)])
            ->where(function ($query) {
                $query->whereRaw("LOWER(status) = 'active'")
                    ->orWhereRaw("LOWER(status) = 'ready for sale'");
            })
            ->where('sold_status', 0)
            ->where('scrap_status', 0)
            ->orderBy('unique_id')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'unique_id' => $product->unique_id,
                    'title' => $product->title,
                    'status' => 'Ready for Sale',
                    'raw_status' => $product->status,
                    'category' => $product->category?->name,
                    'warehouse_location' => $product->warehouseLocation?->name,
                    'manual_status' => (bool) $product->manual_status,
                    'available_units' => $product->manual_status ? (int) ($product->count ?? 0) : 1,
                ];
            });

        return response()->json([
            'success' => true,
            'title' => $title,
            'match_type' => 'exact_case_insensitive',
            'count' => $products->count(),
            'products' => $products,
        ]);
    }
}
