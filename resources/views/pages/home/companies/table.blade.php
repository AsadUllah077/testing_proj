<!-- Table -->
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">S.No</th>

                <th data-col="date" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <div class="flex items-center gap-1">
                        Date
                        <div class="flex flex-col">
                            <a href="{{ route('home.companies.index', array_merge(request()->all(), ['order_by' => 'created_at', 'direction' => 'asc'])) }}"
                                class="text-gray-400 hover:text-blue-600 leading-none">▲</a>
                            <a href="{{ route('home.companies.index', array_merge(request()->all(), ['order_by' => 'created_at', 'direction' => 'desc'])) }}"
                                class="text-gray-400 hover:text-blue-600 leading-none -mt-1">▼</a>
                        </div>
                    </div>
                </th>

                <th data-col="name" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <div class="flex items-center gap-1">
                        Name
                        <div class="flex flex-col">
                            <a href="{{ route('home.companies.index', array_merge(request()->all(), ['order_by' => 'name', 'direction' => 'asc'])) }}"
                                class="text-gray-400 hover:text-blue-600 leading-none">▲</a>
                            <a href="{{ route('home.companies.index', array_merge(request()->all(), ['order_by' => 'name', 'direction' => 'desc'])) }}"
                                class="text-gray-400 hover:text-blue-600 leading-none -mt-1">▼</a>
                        </div>
                    </div>
                </th>

                <th data-col="phone" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                <th data-col="mobile" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mobile
                </th>
                <th data-col="email" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th data-col="country" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Country
                </th>
                <th data-col="state" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">State</th>
                <th data-col="currency" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Currency
                </th>
                <th data-col="favicon" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Favicon</th>
                <th data-col="logo" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Logo</th>
                <th data-col="signature" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Signature
                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
            </tr>
        </thead>

        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($companies as $company)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">{{ $loop->iteration }}</td>

                    <td data-col="date" class="px-6 py-4 text-sm">
                        {{ $company->created_at->format('d/m/Y') }}</td>
                    <td data-col="name" class="px-6 py-4 text-sm">{{ $company->name }}</td>
                    <td data-col="phone" class="px-6 py-4 text-sm">{{ $company->phone ?? '-' }}</td>
                    <td data-col="mobile" class="px-6 py-4 text-sm">{{ $company->mobile ?? '-' }}
                    </td>
                    <td data-col="email" class="px-6 py-4 text-sm">{{ $company->email ?? '-' }}</td>
                    <td data-col="country" class="px-6 py-4 text-sm">
                        {{ $company->country->name ?? '-' }}</td>
                    <td data-col="state" class="px-6 py-4 text-sm">{{ $company->state->name ?? '-' }}
                    </td>
                    <td data-col="currency" class="px-6 py-4 text-sm">
                        {{ $company->currency->name ?? '-' }}
                        @if ($company->currency?->symbol)
                            ({{ $company->currency->symbol }})
                        @endif
                    </td>
                    <td data-col="favicon" class="px-6 py-4 text-sm">
                        @if ($company->favicon)
                            <img src="{{ asset('storage/' . $company->favicon) }}" class="h-6 w-6 rounded object-cover shadow-sm border border-gray-200">
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td data-col="logo" class="px-6 py-4 text-sm">
                        @if ($company->logo)
                            <img src="{{ asset('storage/' . $company->logo) }}" class="h-8 w-8 rounded object-cover">
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td data-col="signature" class="px-6 py-4 text-sm">
                        @if ($company->signature)
                            <img src="{{ asset('storage/' . $company->signature) }}" class="h-8 w-auto">
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>

                    <!-- Action Dropdown -->
                    <td class="px-6 py-4 text-sm">
                        <div class="relative">
                            <button onclick="toggleMenu({{ $company->id }})"
                                class="text-gray-600 hover:text-gray-900">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </button>
                            <div id="menu-{{ $company->id }}"
                                class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                <form action="{{ route('home.companies.destroy', $company->id) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" onclick="return confirm('Are you sure?')"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete
                                    </button>
                                </form>
                                <a href="{{ route('home.companies.edit', $company->id) }}"
                                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.232 5.232l3.536 3.536M9 11l6.232-6.232a2 2 0 112.828 2.828L11.828 13.828a2 2 0 01-1.414.586H9v-2.414a2 2 0 01.586-1.414z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 20H5a2 2 0 01-2-2V5a2 2 0 012-2h7" />
                                    </svg>
                                    Edit
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="px-6 py-4 text-center text-sm text-gray-500">No
                        companies found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$companies" />
