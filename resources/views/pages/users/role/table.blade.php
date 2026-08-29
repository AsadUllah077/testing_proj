<!-- Table -->
<div class="overflow-x-auto w-full">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">#</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role Name</th>
                <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse($roles as $role)
                <tr id="row-{{ $role->id }}" class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-500">
                        {{ $loop->iteration }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="h-9 w-9 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" opacity="0.5" transform="translate(0, 7)" />
                                </svg>
                            </div>
                            <div>
                                <span class="font-medium text-gray-900">{{ $role->name ?? 'N/A' }}</span>
                                <p class="text-xs text-gray-400 sm:hidden mt-0.5">{{ $role->created_at?->format('d M, Y') }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="hidden sm:table-cell px-4 py-3 text-gray-500">
                        {{ $role->created_at?->format('d M, Y') }}
                    </td>
                    <td class="px-4 py-3 text-center relative">
                        <button
                            class="text-gray-400 hover:text-gray-600 dropdown-btn p-1.5 rounded hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>
                        <div class="dropdown-menu hidden absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-20 overflow-hidden">
                            <button type="button"
                                class="delete-btn w-full text-left px-4 py-2.5 hover:bg-red-50 flex items-center gap-3 text-sm text-gray-700 hover:text-red-600 transition-colors"
                                data-url="{{ route('users.role.destroy', $role->id) }}"
                                data-row="#row-{{ $role->id }}" onclick="confirmDelete(this)">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                Delete
                            </button>
                            <a href="{{ route('users.role.edit', $role->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Edit
                            </a>
                            <a href="{{ route('users.role.permission.create', $role->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-green-50 flex items-center gap-3 text-sm text-gray-700 hover:text-green-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Manage Permissions
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-10 text-center text-gray-400">
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                        </svg>
                        No roles found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$roles" />
