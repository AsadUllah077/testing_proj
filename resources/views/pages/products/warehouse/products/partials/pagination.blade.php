{{-- resources/views/pages/products/warehouse/products/partials/pagination.blade.php --}}
@if(isset($products) && $products->hasPages())
<div id="pagination-wrapper" class="px-4 py-3 border-t border-gray-200">
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-700">
            Showing <span class="font-medium">{{ $products->firstItem() }}</span>
            to <span class="font-medium">{{ $products->lastItem() }}</span>
            of <span class="font-medium">{{ $products->total() }}</span> results
        </div>
        <div class="flex items-center space-x-2">
            @if($products->onFirstPage())
                <button class="px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 bg-gray-50 cursor-not-allowed opacity-50" disabled>
                    Previous
                </button>
            @else
                <a href="{{ $products->previousPageUrl() }}"
                   class="px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 ajax-pagination">
                    Previous
                </a>
            @endif

            @foreach($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                @if($page == $products->currentPage())
                    <button class="px-3 py-2 bg-[#0078C8] text-white rounded text-sm font-medium">
                        {{ $page }}
                    </button>
                @else
                    <a href="{{ $url }}"
                       class="px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 ajax-pagination">
                        {{ $page }}
                    </a>
                @endif
            @endforeach

            @if($products->hasMorePages())
                <a href="{{ $products->nextPageUrl() }}"
                   class="px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 ajax-pagination">
                    Next
                </a>
            @else
                <button class="px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 bg-gray-50 cursor-not-allowed opacity-50" disabled>
                    Next
                </button>
            @endif
        </div>
    </div>
</div>
@endif
