@if($brackets->count())
    <table class="w-full text-sm">
        <thead class="text-left text-[10px] text-gray-400 uppercase tracking-wider bg-gray-50">
            <tr>
                <th class="px-4 py-3">#</th>
                <th class="px-4 py-3">Min Income (PKR)</th>
                <th class="px-4 py-3">Max Income (PKR)</th>
                <th class="px-4 py-3">Base Tax (PKR)</th>
                <th class="px-4 py-3">Rate (%)</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($brackets as $b)
            <tr>
                <td class="px-4 py-3 text-gray-500">{{ $b->sort_order }}</td>
                <td class="px-4 py-3 font-medium">{{ number_format($b->min_income) }}</td>
                <td class="px-4 py-3">{{ $b->max_income ? number_format($b->max_income) : '∞ (No Limit)' }}</td>
                <td class="px-4 py-3">{{ number_format($b->base_tax) }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-700">{{ $b->rate }}%</span>
                </td>
                <td class="px-4 py-3 text-right">
                    <button onclick="editBracket({{ $b->id }}, {{ $b->min_income }}, {{ $b->max_income ?? 'null' }}, {{ $b->base_tax }}, {{ $b->rate }}, {{ $b->sort_order }})"
                        class="text-[#0078C8] hover:underline text-xs font-medium mr-3">Edit</button>
                    <button onclick="deleteBracket({{ $b->id }})"
                        class="text-red-500 hover:underline text-xs font-medium">Delete</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if ($brackets instanceof \Illuminate\Pagination\LengthAwarePaginator && $brackets->hasPages())
        <div class="mt-4 bg-white rounded-lg">
            <x-data-table-paginatior :paginator="$brackets" />
        </div>
    @endif
@else
    <div class="p-10 text-center text-gray-400 text-sm">
        No tax brackets configured for this fiscal year.
        <br><button onclick="seedFBR()" class="mt-3 px-4 py-2 bg-[#0078C8] text-white rounded text-xs font-bold">Load FBR 2024-25 Slabs</button>
    </div>
@endif
