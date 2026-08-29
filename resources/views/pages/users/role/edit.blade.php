@extends('layouts.auth')

@section('title', 'Edit Role')

@section('content')
<div class="mt-4 px-3">

    <!-- Header -->
    <div class="p-4 sm:p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center mb-6">
        <h2 class="text-stone-950 text-xl font-normal">Edit Role</h2>
        <a href="{{ route('users.role.index') }}"
            class="w-full sm:w-auto px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] text-center transition-colors">
            Back to List
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
        <form class="space-y-6" id="formSubmit"
            action_url="{{ route('users.role.update', $role->id) }}"
            back_url="{{ route('users.role.index') }}"
            method="POST">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Role Details</label>
                <div>
                    <label class="block text-sm text-gray-600 mb-1.5">Role Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required
                        value="{{ $role->name }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all"
                        placeholder="e.g., Manager">
                    <div id="name_error" class="text-red-500 text-xs mt-1"></div>
                    <p class="text-xs text-gray-400 mt-1">Enter a unique role name</p>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-4 border-t border-gray-100">
                <a href="{{ route('users.role.index') }}"
                    class="text-center px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff] transition-colors">
                    CANCEL
                </a>
                <button type="submit" id="submitBtn"
                    class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-colors">
                    UPDATE ROLE
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Toast -->
<div id="toast" class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
    <div id="toast-content" class="text-sm text-gray-800"></div>
</div>
@endsection

@push('scripts')
@endpush
