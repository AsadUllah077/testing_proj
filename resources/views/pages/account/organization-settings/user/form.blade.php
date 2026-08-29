@php
    $editing = isset($user) && $user;
    $usersIndexRoute = $usersIndexRoute ?? 'account.organization-settings.users';
    $rolesCreateRoute = $rolesCreateRoute ?? 'account.organization-settings.roles.create';
    $rolesEditRoute = $rolesEditRoute ?? 'account.organization-settings.roles.edit';
    $rolesEditPermission = $rolesEditPermission ?? 'account.organization-settings.roles.edit';
    $permissionLabel = $permissionLabel ?? 'accounting';
@endphp

<div class="min-h-screen bg-gray-50/30 py-8">
    <div class="mx-auto w-full px-4 sm:px-6 lg:px-8">
        <div class="mb-5 flex items-center justify-between rounded-xl bg-white p-5">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900">{{ $title }}</h1>
                <p class="mt-1 text-sm text-gray-500">Assign a role and add permissions specifically for this user.</p>
            </div>
            <a href="{{ route($usersIndexRoute) }}"
                class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <section class="rounded-2xl border border-gray-100 bg-white p-6">
                <h2 class="mb-5 text-lg font-bold text-gray-900">User details</h2>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        'first_name' => 'First name',
                        'last_name' => 'Last name',
                        'username' => 'Username',
                        'email' => 'Email',
                        'phone_number' => 'Phone number',
                        'designation' => 'Designation',
                    ] as $field => $label)
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">{{ $label }}</label>
                            <input type="{{ $field === 'email' ? 'email' : 'text' }}" name="{{ $field }}"
                                value="{{ old($field, $editing ? $user->{$field} : '') }}"
                                {{ in_array($field, ['first_name', 'last_name', 'username', 'email', 'phone_number', 'designation']) ? 'required' : '' }}
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0078C8] focus:ring-[#0078C8]">
                        </div>
                    @endforeach

                    <div>
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <label class="block text-sm font-semibold text-gray-700">Role</label>
                            <div class="flex items-center gap-3">
                                @if (auth()->user()->hasPermission($rolesEditPermission))
                                    <a id="edit-role-link" href="#"
                                        data-route-template="{{ route($rolesEditRoute, ['role' => '__ROLE_ID__']) }}"
                                        class="hidden text-xs font-bold text-amber-600 hover:underline">Edit selected role</a>
                                @endif
                                <a href="{{ route($rolesCreateRoute) }}"
                                    class="text-xs font-bold text-[#0078C8] hover:underline">Create {{ $permissionLabel }} role</a>
                            </div>
                        </div>
                        <select id="role-select" name="role_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                            <option value="">Select a role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((int) old('role_id', $editing ? $user->role_id : 0) === $role->id)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">Password</label>
                        <input type="password" name="password" {{ $editing ? '' : 'required' }}
                            placeholder="{{ $editing ? 'Leave blank to keep current password' : 'Minimum 8 characters' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">Profile picture</label>
                        <input type="file" name="profile_pic" accept="image/*"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>

                    <label class="flex items-center gap-3 pt-7 text-sm font-semibold text-gray-700">
                        <input type="hidden" name="status" value="0">
                        <input type="checkbox" name="status" value="1" @checked((bool) old('status', $editing ? $user->status : true))
                            class="h-5 w-5 rounded border-gray-300 text-[#0078C8]">
                        User is enabled
                    </label>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-6">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Direct permissions</h2>
                        <p class="text-sm text-gray-500">Role permissions are checked automatically. You can select additional permissions for this user.</p>
                    </div>
                    <div class="flex gap-2">
                        <input id="permission-search" type="search" placeholder="Search permissions"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <button type="button" id="select-all-permissions"
                            class="rounded-lg bg-blue-50 px-3 py-2 text-sm font-bold text-blue-700">Select all</button>
                        <button type="button" id="clear-permissions"
                            class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-bold text-gray-700">Clear</button>
                    </div>
                </div>

                <div id="permission-list" class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
                    @foreach ($permissions as $module => $modulePermissions)
                        <div class="permission-group rounded-xl border border-gray-200 p-4">
                            <h3 class="mb-3 font-bold capitalize text-gray-800">{{ str_replace(['-', '_'], ' ', $module) }}</h3>
                            <div class="max-h-64 space-y-2 overflow-y-auto pr-1">
                                @foreach ($modulePermissions as $permission)
                                    <label class="permission-item flex items-start gap-2 rounded-lg p-2 text-sm hover:bg-gray-50"
                                        data-name="{{ strtolower($permission->name) }}">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                            @checked(in_array($permission->id, old('permissions', $assignedPermissionIds)))
                                            data-direct-selected="{{ in_array($permission->id, old('permissions', $assignedPermissionIds)) ? '1' : '0' }}"
                                            class="permission-checkbox mt-0.5 rounded border-gray-300 text-[#0078C8]">
                                        <span>{{ $permission->name }}</span>
                                        <span class="inherited-badge ml-auto hidden rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">Inherited</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex justify-end gap-3 rounded-xl bg-white p-5">
                <a href="{{ route($usersIndexRoute) }}"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700">Cancel</a>
                <button type="submit" class="rounded-lg bg-[#0078C8] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#006bb0]">
                    {{ $submitLabel }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        const rolePermissionIds = @json($rolePermissionIds ?? []);
        const editableRoleIds = new Set(@json(array_map('strval', $editableRoleIds ?? [])));
        const roleSelect = document.getElementById('role-select');
        const editRoleLink = document.getElementById('edit-role-link');

        function refreshEditRoleLink() {
            if (!editRoleLink || !roleSelect) return;

            const canEdit = editableRoleIds.has(String(roleSelect.value));
            editRoleLink.classList.toggle('hidden', !canEdit);
            if (canEdit) {
                editRoleLink.href = editRoleLink.dataset.routeTemplate.replace('__ROLE_ID__', roleSelect.value);
            }
        }

        function refreshInheritedPermissions() {
            const inheritedIds = new Set((rolePermissionIds[roleSelect?.value] || []).map(String));

            document.querySelectorAll('.permission-checkbox').forEach(box => {
                const inherited = inheritedIds.has(String(box.value));
                const item = box.closest('.permission-item');

                box.disabled = inherited;
                box.checked = inherited || box.dataset.directSelected === '1';
                item?.querySelector('.inherited-badge')?.classList.toggle('hidden', !inherited);
                item?.classList.toggle('bg-blue-50/50', inherited);
            });
        }

        document.querySelectorAll('.permission-checkbox').forEach(box => {
            box.addEventListener('change', () => {
                if (!box.disabled) {
                    box.dataset.directSelected = box.checked ? '1' : '0';
                }
            });
        });

        roleSelect?.addEventListener('change', () => {
            refreshInheritedPermissions();
            refreshEditRoleLink();
        });
        refreshInheritedPermissions();
        refreshEditRoleLink();

        document.getElementById('permission-search')?.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            document.querySelectorAll('.permission-item').forEach(item => {
                item.classList.toggle('hidden', !item.dataset.name.includes(term));
            });
            document.querySelectorAll('.permission-group').forEach(group => {
                group.classList.toggle('hidden', !group.querySelector('.permission-item:not(.hidden)'));
            });
        });

        document.getElementById('select-all-permissions')?.addEventListener('click', () => {
            document.querySelectorAll('.permission-item:not(.hidden) .permission-checkbox:not(:disabled)').forEach(box => {
                box.checked = true;
                box.dataset.directSelected = '1';
            });
        });

        document.getElementById('clear-permissions')?.addEventListener('click', () => {
            document.querySelectorAll('.permission-checkbox:not(:disabled)').forEach(box => {
                box.checked = false;
                box.dataset.directSelected = '0';
            });
        });
    </script>
@endpush
