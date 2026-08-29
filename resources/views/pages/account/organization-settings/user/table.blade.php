@php
    $userEditRouteName = $userEditRouteName ?? 'account.organization-settings.users.edit';
    $userDestroyRouteName = $userDestroyRouteName ?? 'account.organization-settings.users.destroy';
    $displayPermissionPrefix = $displayPermissionPrefix ?? 'account.';
@endphp

<div class="overflow-x-auto w-full p-4">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left">S.No</th>
                <th class="px-4 py-3 text-left">User</th>
                <th class="px-4 py-3 text-left">Role</th>
                <th class="px-4 py-3 text-left">Email</th>
                <th class="px-4 py-3 text-left">Company access</th>
                <th class="px-4 py-3 text-left">Direct permissions</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($users as $user)
                @php
                    $canManage = auth()->user()->isSuperAdmin() || !$user->isSuperAdmin();
                @endphp
                <tr id="row-{{ $user->id }}" class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $users->firstItem() + $loop->index }}</td>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-gray-900">{{ $user->full_name }}</div>
                        <div class="text-xs text-gray-500">{{ $user->username }}</div>
                    </td>
                    <td class="px-4 py-3">{{ $user->role?->name ?? 'No role' }}</td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        @if ((int) $user->company_id === (int) auth()->user()->company_id)
                            <span class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">Primary</span>
                        @else
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">Shared</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                            {{ $user->permissions->filter(fn ($permission) => str_starts_with($permission->name, $displayPermissionPrefix))->count() }} assigned
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $user->status ? 'Enabled' : 'Disabled' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-2">
                            @if ($canManage)
                                <a href="{{ route($userEditRouteName, $user) }}"
                                    class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100">
                                    Edit access
                                </a>
                                @if (!auth()->user()->is($user))
                                    <button type="button"
                                        class="rounded-lg bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-100"
                                        data-url="{{ route($userDestroyRouteName, $user) }}"
                                        data-row="#row-{{ $user->id }}" onclick="confirmDelete(this)">
                                        Delete
                                    </button>
                                @endif
                            @else
                                <span class="text-xs text-gray-400">Protected administrator</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-gray-500">No company users found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$users" />
