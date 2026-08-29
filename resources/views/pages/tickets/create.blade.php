@extends('layouts.auth')
@section('title', 'Log New Issue')

@section('content')
    <x-acccount.loader />
    <div class="mt-4 mx-auto px-3 max-w-12xl">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('ticket.tickets.index') }}" class="hover:text-[#0078C8]">Tickets</a>
            <span>/</span>
            <span class="text-gray-800">Log New Issue</span>
        </div>

        <!-- Header Card -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <h1 class="text-xl font-bold text-gray-900">Log New Issue</h1>
            <p class="text-sm text-gray-500 mt-1">Identify and document obstacles with surgical precision. Our system ensures every detail is routed to the correct department immediately.</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <form id="formSubmit" method="post"
                  action_url="{{ route('ticket.tickets.store') }}"
                  back_url="{{ route('ticket.tickets.index') }}"
                  class="p-6 space-y-5"
                  enctype="multipart/form-data">
                @csrf

                <!-- Issue Title -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Issue Title</label>
                    <input type="text" name="title" id="title"
                        placeholder="e.g., Database synchronisation failure in production"
                        class="w-full py-2.5 px-4 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all bg-gray-50 focus:bg-white"
                        required>
                    <div id="title_error" class="text-red-500 text-xs mt-1"></div>
                </div>

                <!-- Detailed Description -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Detailed Description</label>
                    <textarea name="description" id="description" rows="5"
                        placeholder="Describe the steps to reproduce the issue and the observed behavior..."
                        class="w-full py-2.5 px-4 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all resize-none bg-gray-50 focus:bg-white"
                        required></textarea>
                    <div id="description_error" class="text-red-500 text-xs mt-1"></div>
                </div>

                <!-- Severity Level -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Severity Level</label>
                    <select name="severity" id="severity" required
                        class="select2 w-full py-2.5 px-4 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all bg-gray-50 focus:bg-white">
                        @foreach($severities as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div id="severity_error" class="text-red-500 text-xs mt-1"></div>
                </div>

                <!-- Support Evidence -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Support Evidence (Optional)</label>
                    <div id="dropzone"
                        class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center cursor-pointer hover:border-[#0078C8] hover:bg-blue-50/30 transition-all"
                        onclick="document.getElementById('attachment').click()">
                        <input type="file" id="attachment" name="attachment" class="hidden"
                            accept=".png,.jpg,.jpeg,.pdf,.mp4"
                            onchange="showFileName(this)">
                        <div id="dropzoneContent">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-10 h-10 text-gray-300 mx-auto mb-3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                            </svg>
                            <p class="text-sm text-gray-500 font-medium">Click to upload or drag and drop</p>
                            <p class="text-xs text-gray-400 mt-1">PNG, JPG, PDF, or MP4 (10MB)</p>
                        </div>
                    </div>
                    <div id="attachment_error" class="text-red-500 text-xs mt-1"></div>
                </div>

                <!-- Footer -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pt-2 border-t border-gray-100 gap-3">
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-[#0078C8] flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        Logged as <span class="font-medium text-gray-700">{{ Auth::user()->full_name ?? Auth::user()->name }}</span>
                    </div>
                    <div class="flex items-center gap-3 self-end sm:self-auto">
                        <a href="{{ route('ticket.tickets.index') }}"
                            class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" id="submitBtn"
                            class="px-6 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors">
                            Submit Ticket
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function showFileName(input) {
        if (input.files && input.files[0]) {
            document.getElementById('dropzoneContent').innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-[#0078C8] mx-auto mb-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <p class="text-sm font-medium text-gray-700">${input.files[0].name}</p>
                <p class="text-xs text-gray-400 mt-0.5">${(input.files[0].size / 1024 / 1024).toFixed(2)} MB</p>
            `;
        }
    }
</script>
@endpush
