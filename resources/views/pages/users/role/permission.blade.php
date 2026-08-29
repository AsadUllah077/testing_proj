@extends('layouts.auth')

@section('title', 'Assign Permissions to Role')

@section('content')
<div class="mt-4 px-3">

    <!-- Header -->
    <div class="p-4 sm:p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center mb-6">
        <div>
            <h2 class="text-stone-950 text-xl font-normal">Assign Permissions</h2>
            <p class="text-sm text-gray-600 mt-0.5">Role: <strong>{{ $role->name }}</strong></p>
        </div>
        <a href="javascript:history.back()"
            class="w-full sm:w-auto px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Go Back
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form id="formSubmit" method="post" action="{{ route('users.role.permission.store', $role->id) }}" back_url="{{ route('users.role.index') }}">
            @csrf

            <!-- Role Info Cards -->
            <div class="p-4 sm:p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">

                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" opacity="0.5" transform="translate(0, 7)" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-medium text-gray-800 truncate">{{ $role->name }}</h3>
                                <p class="text-xs text-gray-500">{{ $role->company->name ?? 'System Role' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Users Count Card -->
                    <div class="bg-white border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-blue-50 transition-colors" id="usersCountCard">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600" id="usersCount">{{ $usersCount ?? 0 }}</div>
                            <div class="text-xs text-gray-500">Users</div>
                            <div class="text-xs text-blue-500 mt-1">Click to view users</div>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ count($assignedPermissionIds) }}</div>
                            <div class="text-xs text-gray-500">Current Permissions</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Permissions Selection -->
            <div class="p-4 sm:p-6">

                <!-- Section header + action buttons -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <h3 class="text-base font-medium text-gray-800">Select Permissions</h3>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="selectAllBtn"
                            class="px-3 py-1.5 text-xs border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                            Select All
                        </button>
                        <button type="button" id="deselectAllBtn"
                            class="px-3 py-1.5 text-xs border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                            Deselect All
                        </button>
                        <button type="button" id="selectVisibleBtn"
                            class="px-3 py-1.5 text-xs border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                            Select Visible
                        </button>
                    </div>
                </div>

                <!-- Search -->
                <div class="mb-5">
                    <input type="text" id="permissionSearch"
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all"
                        placeholder="Search permissions...">
                    <div class="mt-1.5 text-xs text-gray-400" id="searchResultInfo"></div>
                </div>

                <!-- Permissions Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3" id="permissionsContainer">
                    @foreach($permissions as $permission)
                    <div class="permission-item border border-gray-200 rounded-lg p-3 sm:p-4 hover:border-[#0078C8] transition-colors cursor-pointer"
                        data-permission-id="{{ $permission->id }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-start gap-2.5 min-w-0">
                                <input type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->id }}"
                                    id="permission_{{ $permission->id }}"
                                    class="mt-0.5 permission-checkbox flex-shrink-0"
                                    {{ in_array($permission->id, $assignedPermissionIds) ? 'checked' : '' }}>
                                <div class="min-w-0">
                                    <label for="permission_{{ $permission->id }}" class="font-medium text-gray-800 cursor-pointer text-sm leading-tight">
                                        {{ $permission->name }}
                                    </label>
                                    <p class="text-xs text-gray-400 mt-0.5 truncate">
                                        {{ $permission->company->name ?? 'System Permission' }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded flex-shrink-0 {{ $permission->company_id ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $permission->company_id ? 'Company' : 'System' }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Selected Count -->
                <div class="mt-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <span class="text-sm font-medium text-blue-800" id="selectedCount">0</span>
                            <span class="text-sm text-blue-600"> permissions selected</span>
                            <span class="text-xs text-gray-500 ml-1" id="visibleSelectedCount"></span>
                        </div>
                        <div class="text-sm text-blue-600 flex-shrink-0">
                            Total: <span id="totalCount">{{ $permissions->count() }}</span> permissions
                        </div>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-2">
                    <a href="{{ route('users.role.index') }}"
                        class="text-center px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff] transition-colors">
                        CANCEL
                    </a>
                    <button type="submit" id="submitBtn"
                        class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-colors">
                        ASSIGN PERMISSIONS
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>

<!-- Users Modal -->
<div id="usersModal" class="fixed inset-0 bg-black/50 overflow-y-auto z-50 hidden p-4 sm:p-6">
    <div class="relative mx-auto mt-8 sm:mt-16 w-full max-w-3xl shadow-lg rounded-xl bg-white">
        <!-- Modal Header -->
        <div class="flex justify-between items-start p-4 sm:p-5 border-b">
            <div>
                <h3 class="text-base sm:text-lg font-semibold text-gray-800">
                    Users with Role: <span class="text-blue-600">{{ $role->name }}</span>
                </h3>
                <p class="text-sm text-gray-500 mt-0.5">Total: <span id="modalUsersCount">{{ $usersCount ?? 0 }}</span> users</p>
            </div>
            <button id="closeModal" class="text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100 transition-colors flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Modal Content -->
        <div class="p-4 sm:p-5">
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100" id="usersTableBody">
                        <tr id="loadingRow">
                            <td colspan="2" class="px-4 py-8 text-center">
                                <div class="flex justify-center items-center gap-2 text-gray-400">
                                    <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                    </svg>
                                    Loading users...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3" id="paginationContainer" style="display: none;">
                <div class="text-sm text-gray-600" id="paginationInfo">
                    Showing <span id="startRow">0</span>–<span id="endRow">0</span> of <span id="totalRows">0</span>
                </div>
                <div class="flex gap-2" id="paginationButtons">
                    <button id="prevPage" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors" disabled>
                        Previous
                    </button>
                    <button id="nextPage" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors" disabled>
                        Next
                    </button>
                </div>
            </div>

            <!-- No Users -->
            <div id="noUsersMessage" class="text-center py-10 text-gray-400" style="display: none;">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <p class="text-sm">No users found with this role</p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-4 sm:px-5 py-4 border-t bg-gray-50 rounded-b-xl flex justify-end">
            <button id="closeModalBtn" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
    <div id="toast-content" class="text-sm text-gray-800"></div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let selectedCount = $('.permission-checkbox:checked').length;
    let totalCount = {{ $permissions->count() }};
    let searchActive = false;

    updateSelectedCount();
    updateSearchInfo();

    function updateSelectedCount() {
        selectedCount = $('.permission-checkbox:checked').length;
        $('#selectedCount').text(selectedCount);

        let visibleSelected = $('.permission-item:visible .permission-checkbox:checked').length;
        let visibleTotal = $('.permission-item:visible').length;

        if (searchActive && visibleTotal > 0) {
            $('#visibleSelectedCount').text(`(${visibleSelected}/${visibleTotal} visible)`);
        } else {
            $('#visibleSelectedCount').text('');
        }
    }

    function updateSearchInfo() {
        let visibleCount = $('.permission-item:visible').length;
        let hiddenCount = totalCount - visibleCount;

        if ($('#permissionSearch').val().trim() !== '') {
            searchActive = true;
            $('#searchResultInfo').text(`Showing ${visibleCount} of ${totalCount} permissions. ${hiddenCount} hidden.`);
            $('#totalCount').text(visibleCount);
        } else {
            searchActive = false;
            $('#searchResultInfo').text('');
            $('#totalCount').text(totalCount);
        }
        updateSelectedCount();
    }

    $(document).on('change', '.permission-checkbox', updateSelectedCount);

    $('#selectAllBtn').on('click', function() {
        if (searchActive) {
            if (confirm("Select ALL permissions (including hidden ones)?")) {
                $('.permission-checkbox').prop('checked', true);
            }
        } else {
            $('.permission-checkbox').prop('checked', true);
        }
        updateSelectedCount();
        showToast('All permissions selected', 'success');
    });

    $('#selectVisibleBtn').on('click', function() {
        $('.permission-item:visible .permission-checkbox').prop('checked', true);
        updateSelectedCount();
        showToast(`Selected ${$('.permission-item:visible').length} visible permissions`, 'success');
    });

    $('#deselectAllBtn').on('click', function() {
        if (searchActive) {
            if (confirm("Deselect ALL permissions (including hidden ones)?")) {
                $('.permission-checkbox').prop('checked', false);
            }
        } else {
            $('.permission-checkbox').prop('checked', false);
        }
        updateSelectedCount();
        showToast('All permissions deselected', 'success');
    });

    $('#permissionSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        $('.permission-item').each(function() {
            const permissionText = $(this).text().toLowerCase();
            $(this).toggle(permissionText.includes(searchTerm));
        });
        updateSearchInfo();
    });

    $('#permissionSearch').on('keydown', function(e) {
        if (e.key === 'Escape') {
            $(this).val('');
            $('.permission-item').show();
            updateSearchInfo();
        }
    });

    // Users Modal
    let currentPage = 1;
    const usersPerPage = 10;
    let totalUsers = 0;
    let currentSearch = '';

    $('#usersCountCard').on('click', function() {
        $('#usersModal').removeClass('hidden');
        loadUsers();
    });

    $('#closeModal, #closeModalBtn').on('click', function() {
        $('#usersModal').addClass('hidden');
        currentSearch = '';
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && !$('#usersModal').hasClass('hidden')) {
            $('#usersModal').addClass('hidden');
            currentSearch = '';
        }
    });

    $('#prevPage').on('click', function() {
        if (currentPage > 1) { currentPage--; loadUsers(); }
    });

    $('#nextPage').on('click', function() {
        if (currentPage * usersPerPage < totalUsers) { currentPage++; loadUsers(); }
    });

    function loadUsers() {
        $('#loadingRow').show();
        $('#usersTableBody').find('tr:not(#loadingRow)').remove();
        $('#paginationContainer').hide();
        $('#noUsersMessage').hide();

        $.ajax({
            url: '{{ route("users.role.getUsers", $role->id) }}',
            type: 'GET',
            data: { search: currentSearch, page: currentPage, per_page: usersPerPage },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                $('#loadingRow').hide();
                if (response.success && response.users.data.length > 0) {
                    totalUsers = response.users.total;
                    $('#modalUsersCount').text(totalUsers);

                    response.users.data.forEach(function(user) {
                        const row = `
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                            <span class="text-blue-600 text-xs font-semibold">
                                                ${user.initials || (user.first_name ? user.first_name.charAt(0) + (user.last_name ? user.last_name.charAt(0) : '') : 'U')}
                                            </span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">${user.full_name || user.name || user.username || 'N/A'}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">${user.email || 'N/A'}</td>
                            </tr>
                        `;
                        $('#usersTableBody').append(row);
                    });

                    updatePagination(response.users);
                    $('#paginationContainer').show();
                } else {
                    $('#noUsersMessage').show();
                }
            },
            error: function() {
                $('#loadingRow').hide();
                $('#noUsersMessage').show().html(`
                    <svg class="w-12 h-12 mx-auto text-red-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <p class="text-sm">Error loading users. Please try again.</p>
                `);
            }
        });
    }

    function updatePagination(pagination) {
        const start = ((currentPage - 1) * usersPerPage) + 1;
        const end = Math.min(currentPage * usersPerPage, pagination.total);
        $('#startRow').text(start);
        $('#endRow').text(end);
        $('#totalRows').text(pagination.total);
        $('#prevPage').prop('disabled', currentPage === 1);
        $('#nextPage').prop('disabled', currentPage === pagination.last_page);
    }

    function showToast(message, type = 'info') {
        const toast = $('#toast');
        $('#toast-content').text(message);
        toast.removeClass('hidden');
        setTimeout(() => toast.addClass('hidden'), 3000);
    }
});
</script>
@endpush
