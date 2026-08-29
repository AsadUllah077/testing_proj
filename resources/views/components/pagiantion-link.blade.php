@props(['items'])
<div id="pagination-wrapper" class="flex justify-between items-center pt-4 border-t border-gray-200 mb-3 mt-3 py-3 px-3">
    <div class="text-sm text-gray-600">
        Showing {{ $items->firstItem() }} to {{ $items->lastItem() }}
        of {{ $items->total() }} Records
    </div>
    <div class="flex items-center space-x-2">
        {{ $items->appends(request()->except('page'))->links() }}
    </div>
</div>
