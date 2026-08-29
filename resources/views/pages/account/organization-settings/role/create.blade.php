@extends('layouts.auth')

@php
    $pageTitle = $pageTitle ?? 'Create Accounting Role';
    $pageDescription = $pageDescription ?? 'Create a role using accounting permissions only.';
    $permissionPrefix = $permissionPrefix ?? 'account.';
    $backRoute = $backRoute ?? 'account.organization-settings.users.create';
    $storeRoute = $storeRoute ?? 'account.organization-settings.roles.store';
    $formAction = $formAction ?? route($storeRoute);
    $formMethod = $formMethod ?? 'POST';
    $submitLabel = $submitLabel ?? 'Create role';
    $assignedPermissionIds = $assignedPermissionIds ?? [];
    $moduleLabel = ucfirst(explode('.', $permissionPrefix)[0]);
    $roleName = isset($role) ? $role->name : '';
@endphp

@section('title', $pageTitle)

@section('content')
    <div class="min-h-screen bg-gray-50/30 py-8">
        <div class="mx-auto w-full px-4 sm:px-6 lg:px-8">
            <div class="mb-5 flex items-center justify-between rounded-xl bg-white p-5">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900">{{ $pageTitle }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ $pageDescription }}</p>
                </div>
                <a href="{{ route($backRoute) }}"
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

            <form method="POST" action="{{ $formAction }}" class="space-y-5">
                @csrf
                @if (strtoupper($formMethod) !== 'POST')
                    @method($formMethod)
                @endif

                <section class="rounded-2xl border border-gray-100 bg-white p-6">
                    <label class="mb-2 block text-sm font-bold text-gray-700">Role name</label>
                    <input type="text" name="name" value="{{ old('name', $roleName) }}" required maxlength="100"
                        placeholder="For example: Accounts Receivable Officer"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#0078C8] focus:ring-[#0078C8]">
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ $moduleLabel }} permissions</h2>
                            <p class="text-sm text-gray-500">Only permissions beginning with <code>{{ $permissionPrefix }}</code> are available.</p>
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

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
                        @foreach ($permissions as $module => $modulePermissions)
                            <div class="permission-group rounded-xl border border-gray-200 p-4">
                                <h3 class="mb-3 font-bold capitalize text-gray-800">{{ str_replace(['-', '_'], ' ', $module) }}</h3>
                                <div class="max-h-64 space-y-2 overflow-y-auto pr-1">
                                    @foreach ($modulePermissions as $permission)
                                        <label class="permission-item flex items-start gap-2 rounded-lg p-2 text-sm hover:bg-gray-50"
                                            data-name="{{ strtolower($permission->name) }}">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                @checked(in_array($permission->id, old('permissions', $assignedPermissionIds)))
                                                class="permission-checkbox mt-0.5 rounded border-gray-300 text-[#0078C8]">
                                            <span>{{ $permission->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="flex justify-end gap-3 rounded-xl bg-white p-5">
                    <a href="{{ route($backRoute) }}"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700">Cancel</a>
                    <button type="submit" class="rounded-lg bg-[#0078C8] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#006bb0]">
                        {{ $submitLabel }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
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
            document.querySelectorAll('.permission-item:not(.hidden) .permission-checkbox').forEach(box => box.checked = true);
        });

        document.getElementById('clear-permissions')?.addEventListener('click', () => {
            document.querySelectorAll('.permission-checkbox').forEach(box => box.checked = false);
        });
    </script>
@endpush
