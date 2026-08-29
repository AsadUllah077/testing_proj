<div>
    {{-- instanceof \Illuminate\Pagination\AbstractPaginator --}}
    @if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->hasPages())
        <div id="pagination-wrapper"
            class="px-6 py-4 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">

            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600">Page</span>

                @if (method_exists($paginator, 'lastPage') && $paginator->lastPage() <= 100)
                    <select class="pagination-select border border-gray-300 rounded text-sm p-1 bg-white"
                        data-page-name="{{ method_exists($paginator, 'getPageName') ? $paginator->getPageName() : 'page' }}">
                        @for ($i = 1; $i <= $paginator->lastPage(); $i++)
                            <option value="{{ $i }}" {{ $paginator->currentPage() == $i ? 'selected' : '' }}>
                                {{ $i }}
                            </option>
                        @endfor
                    </select>
                @elseif(method_exists($paginator, 'lastPage'))
                    <input type="number" value="{{ $paginator->currentPage() }}" min="1"
                        max="{{ $paginator->lastPage() }}"
                        class="pagination-input w-20 border border-gray-300 rounded text-sm p-1 text-center"
                        data-page-name="{{ method_exists($paginator, 'getPageName') ? $paginator->getPageName() : 'page' }}"
                        title="Type page number and press Enter">
                @endif

                @if (method_exists($paginator, 'lastPage') && $paginator->total() > 0)
                    <span class="text-sm text-gray-600">
                        Showing {{ $paginator->firstItem() }}
                        to {{ $paginator->lastItem() }}
                        of {{ $paginator->total() }} records
                        (Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }})
                    </span>
                @endif
            </div>

            <div>
                {{ $paginator->appends(request()->query())->links() }}
            </div>
        </div>
    @endif
</div>
