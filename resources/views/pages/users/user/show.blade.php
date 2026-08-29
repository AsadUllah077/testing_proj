@extends('layouts.auth')

@section('title', 'User Details')

@section('content')
    @php
        $companies = \App\Models\Company::pluck('name', 'id')->toArray();
    @endphp

    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                <!-- Header -->
                <div class=" text-gray-900 p-4 flex items-center justify-between rounded-md ">

                    <!-- Left section: avatar + name + status -->
                    <div class="flex items-center space-x-4">
                        @if ($user->profile_pic)
                            <img src="{{ asset('storage/' . $user->profile_pic) }}" alt="Profile Picture"
                                class="w-25 h-25 rounded-full object-cover">
                        @else
                            <img src="{{ asset('assets/images/default.png') }}" alt="Profile Picture"
                                class="w-25 h-25 rounded-full object-cover">
                        @endif

                        <div>
                            <h3 class="text-2xl font-semibold">{{ $user->first_name }} {{ $user->last_name }}</h3>
                            @if ($user->status)
                                <span
                                    class="mt-1 inline-block bg-green-700 text-white text-lg font-semibold px-2 py-[4px] rounded-full">
                                    Active
                                </span>
                            @else
                                <span
                                    class="mt-1 inline-block bg-gray-600 text-white text-lg font-semibold px-2 py-[4px] rounded-full">
                                    Inactive
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Right section: Back button -->
                    <a href="javascript:void(0)" onclick="window.history.back(); return false;"
                        class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition">
                        Back to User List
                    </a>
                </div>


                <!-- Main Content -->
                <div class="mx-auto  rounded-md shadow-sm p-6 space-y-8 mt-5">

                    <!-- ================= PROFILE + DETAILS ================= -->
                    <div class="grid md:grid-cols-2 gap-8 items-start">

                        <!-- LEFT: PROFILE + BASIC DETAILS -->
                        <div class="bg-white border border-gray-200 p-6">


                            <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-[#0078C8]">Basic
                                Details</h3>
                            <div class="grid grid-cols-2 gap-y-3">
                                <div>
                                    <p class="text-sm text-gray-500">Email</p>
                                    <p class="text-base text-gray-900 font-medium">{{ $user->email }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Phone Number</p>
                                    <p class="text-base text-gray-900 font-medium">{{ $user->phone_number ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Company</p>
                                    <p class="text-base text-gray-900 font-medium">{{ $user->company->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Designation</p>
                                    <p class="text-base text-gray-900 font-medium">{{ $user->designation ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Linked ERP Employee</p>

                                    @if ($user->employee)
                                        <a href="{{ route('hrm.employee.show', $user->employee->id) }}"
                                            class="text-base text-[#0078C8] font-medium hover:underline">
                                            {{ $user->employee->full_name }}
                                        </a>
                                    @else
                                        <p class="text-base text-gray-900 font-medium">—</p>
                                    @endif

                                </div>
                            </div>
                        </div>

                        <!-- RIGHT: LOGIN & SECURITY -->
                        <div class="bg-white border border-gray-200 p-6">

                            <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-[#0078C8]">Login &
                                Security</h3>
                            <div class="grid grid-cols-2 gap-y-3">
                                <div>
                                    <p class="text-sm text-gray-500">Username</p>
                                    <p class="text-base text-gray-900 font-medium">{{ $user->username ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Email</p>
                                    <p class="text-base text-gray-900 font-medium">{{ $user->email }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Last Login</p>
                                    <p class="text-base text-gray-900 font-medium">{{ $user->lastLogin?->created_at }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500"></p>
                                    <p class="text-base text-gray-900 font-medium"></p>
                                </div>

                            </div>
                        </div>

                    </div>
                    <div class="grid grid-cols-1 gap-8 items-start">
                        <div class="bg-white border border-gray-200 p-6 rounded-lg shadow-sm">
                            <h3
                                class="text-lg font-semibold text-gray-900 mb-6 border-b pb-3 border-[#0078C8] flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Role & Permissions
                            </h3>

                            <div class="space-y-6">
                                <!-- Role Information -->
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 14l9-5-9-5-9 5 9 5z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 14l9-5-9-5-9 5 9 5z" opacity="0.5"
                                                        transform="translate(0, 7)" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Assigned Role</h4>
                                                <p class="text-2xl font-bold text-gray-900">
                                                    {{ $user->role->name ?? 'No Role Assigned' }}</p>
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <div
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                </svg>
                                                {{ $user->role?->permissions->count() ?? 0 }} Permissions
                                            </div>
                                        </div>
                                    </div>
                                </div>`

                                <!-- Permissions List -->
                                @if ($user->role && $user->role->permissions->count() > 0)
                                    <div>
                                        <div class="flex items-center justify-between mb-4">
                                            <h4 class="font-medium text-gray-800">Assigned Permissions</h4>
                                            <span class="text-sm text-gray-500">{{ $user->role->permissions->count() }}
                                                total</span>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                            @foreach ($user->role->permissions as $permission)
                                                <div
                                                    class="border border-gray-200 rounded-lg p-3 hover:border-[#0078C8] transition-colors">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <svg class="w-4 h-4 text-green-500" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                <span
                                                                    class="font-medium text-gray-800">{{ $permission->name }}</span>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span
                                                                    class="text-xs px-2 py-1 rounded {{ $permission->company_id ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                                                    {{ $permission->company_id ? 'Company' : 'System' }}
                                                                </span>
                                                                @if ($permission->company)
                                                                    <span
                                                                        class="text-xs text-gray-500">{{ $permission->company->name }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-8 border border-gray-200 rounded-lg">
                                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        <p class="text-gray-500">No permissions assigned to this role</p>
                                    </div>
                                @endif

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
                                    @if ($user->role)
                                        <a href="{{ route('users.role.permission.create', $user->role->id) }}"
                                            class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Manage Role Permissions
                                        </a>
                                    @endif

                                    <a href="{{ route('users.role.index') }}"
                                        class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff] flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        View All Roles
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================= AUDIT INFORMATION ================= -->
                    @if ($user->logs->count() > 0)
                        <div class="bg-white border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-[#0078C8]">Audit
                                Information</h3>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm text-left text-gray-700">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 font-medium text-gray-600">#</th>
                                            <th class="px-4 py-3 font-medium text-gray-600">Action</th>
                                            <th class="px-4 py-3 font-medium text-gray-600">Performed By</th>
                                            <th class="px-4 py-3 font-medium text-gray-600">Changes</th>
                                            <th class="px-4 py-3 font-medium text-gray-600">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach ($user->logs as $index => $log)
                                            <tr class="hover:bg-gray-50 transition">
                                                <td class="px-4 py-3">{{ $index + 1 }}</td>
                                                <td class="px-4 py-3 capitalize">
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                                        {{ $log->action === 'update'
                                                            ? 'bg-blue-100 text-blue-700'
                                                            : ($log->action === 'create'
                                                                ? 'bg-green-100 text-green-700'
                                                                : ($log->action === 'delete'
                                                                    ? 'bg-red-100 text-red-700'
                                                                    : 'bg-gray-100 text-gray-700')) }}">
                                                        {{ ucfirst($log->action) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    {{ $log->actor ? $log->actor->first_name . ' ' . $log->actor->last_name : 'System' }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-600">
                                                    @if ($log->changed_fields)
                                                        <ul class="space-y-1">
                                                            @foreach ($log->changed_fields as $field => $change)
                                                                @php
                                                                    $oldValue = $change['old'] ?? '—';
                                                                    $newValue = $change['new'] ?? '—';
                                                                    if ($field === 'company_id') {
                                                                        $oldValue = $companies[$oldValue] ?? '—';
                                                                        $newValue = $companies[$newValue] ?? '—';
                                                                        $field = 'Company';
                                                                    } else {
                                                                        $field = ucfirst(str_replace('_', ' ', $field));
                                                                    }
                                                                @endphp
                                                                <li>
                                                                    <span
                                                                        class="font-medium text-gray-800">{{ $field }}</span>:
                                                                    <span
                                                                        class="text-red-600 line-through">{{ $oldValue }}</span>
                                                                    →
                                                                    <span
                                                                        class="text-green-600">{{ $newValue }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-gray-400 italic">No details available</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-gray-600">
                                                    {{ \Carbon\Carbon::parse($log->created_at)->format('d M, Y h:i A') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-[#0078C8]">Audit
                                Information</h3>
                            <p class="text-gray-500 text-sm italic">No logs recorded for this user yet.</p>
                        </div>
                    @endif

                    <!-- Buttons -->
                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                        <a href="{{ route('users.user.index') }}"
                            class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff]">
                            BACK
                        </a>
                        <a href="{{ route('users.user.edit', $user->id) }}"
                            class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                            EDIT USER
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
