@extends('layouts.auth')

@section('title', 'Draft New Policy')

@section('content')
    <div class="mt-4 px-3">

        {{-- Page Header --}}
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-widest text-[#0078C8] mb-1">Quality Management Protocol</p>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <h1 class="text-2xl font-bold text-gray-900">Draft New Policy</h1>
                <div class="flex gap-2">
                    <a href="{{ route('qms.policy.index') }}"
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                        Back
                    </a>
                </div>
            </div>
        </div>

        <form id="formSubmit"
              method="POST"
              action_url="{{ route('qms.policy.store') }}"
              back_url="{{ route('qms.policy.index') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                {{-- Left: Main Content --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- Title --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Policy Title <span class="text-red-500">*</span></label>
                        <input type="text"
                               name="title"
                               id="title"
                               placeholder="e.g., Data State Integrity & Ethics Policy"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100 outline-none"
                               value="{{ old('title') }}" />
                    </div>

                    {{-- Content --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Policy Content & Briefing</label>

                        {{-- Simple Toolbar --}}
                        <div class="flex items-center gap-1 mb-3 pb-3 border-b border-gray-100">
                            <button type="button" onclick="execCmd('bold')"
                                class="p-1.5 rounded hover:bg-gray-100 text-gray-600 font-bold text-sm w-8 h-8 flex items-center justify-center">B</button>
                            <button type="button" onclick="execCmd('italic')"
                                class="p-1.5 rounded hover:bg-gray-100 text-gray-500 italic text-sm w-8 h-8 flex items-center justify-center">I</button>
                            <button type="button" onclick="execCmd('underline')"
                                class="p-1.5 rounded hover:bg-gray-100 text-gray-500 underline text-sm w-8 h-8 flex items-center justify-center">U</button>
                            <div class="w-px h-5 bg-gray-200 mx-1"></div>
                            <button type="button" onclick="execCmd('insertUnorderedList')"
                                class="p-1.5 rounded hover:bg-gray-100 text-gray-500 text-sm w-8 h-8 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </button>
                            <button type="button" onclick="execCmd('insertOrderedList')"
                                class="p-1.5 rounded hover:bg-gray-100 text-gray-500 text-sm w-8 h-8 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </button>
                        </div>

                        <div id="contentEditor"
                             contenteditable="true"
                             class="min-h-[280px] w-full border border-gray-200 rounded-lg p-4 text-sm text-gray-700 focus:outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100"
                             placeholder="Begin drafting the policy content here..."></div>
                        <input type="hidden" name="content" id="contentHidden" />
                    </div>

                    {{-- Attachments --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Policy Attachments</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-[#0078C8] transition-colors bg-gray-50">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="attachments" class="relative cursor-pointer bg-white rounded-md font-medium text-[#0078C8] hover:text-[#006bb0] focus-within:outline-none">
                                        <span>Upload files</span>
                                        <input id="attachments" name="attachments[]" type="file" multiple class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">
                                    PDF, DOC, DOCX up to 10MB each
                                </p>
                                <div id="file-list" class="mt-2 text-xs text-gray-500 text-left"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Buttons --}}
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="saveDraft()"
                            class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                            Save as Draft
                        </button>
                        <button type="submit" id="submitBtn"
                            class="px-5 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">
                            Submit for Approval
                        </button>
                    </div>
                </div>

                {{-- Right: Classifier --}}
                <div class="space-y-4">

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Classifier</h3>

                        {{-- Department --}}
                        <div class="mb-4">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Department</label>
                            <select name="department"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100 outline-none">
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->department }}" {{ old('department') == $dept->department ? 'selected' : '' }}>
                                        {{ $dept->department }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Priority --}}
                        <div class="mb-2">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Priority</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $val => $label)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="priority" value="{{ $val }}" class="sr-only priority-radio"
                                               {{ old('priority', 'medium') === $val ? 'checked' : '' }}>
                                        <div class="priority-btn text-center py-2 rounded-lg border text-xs font-medium transition-all
                                            {{ old('priority', 'medium') === $val
                                                ? ($val === 'low' ? 'bg-gray-600 text-white border-gray-600' : ($val === 'medium' ? 'bg-yellow-500 text-white border-yellow-500' : 'bg-red-500 text-white border-red-500'))
                                                : 'bg-white text-gray-500 border-gray-200 hover:border-gray-400' }}">
                                            {{ $label }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Hidden status (set by button) --}}
                        <input type="hidden" name="status" id="statusField" value="{{ old('status', 'draft') }}" />
                    </div>

                    {{-- AI Writing Guidelines (decorative) --}}
                    <div class="bg-blue-50 rounded-xl border border-blue-100 p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span class="text-xs font-semibold text-[#0078C8]">AI Writing Guidelines</span>
                        </div>
                        <ul class="space-y-1.5 text-xs text-blue-700">
                            <li class="flex items-start gap-1.5">
                                <span class="mt-0.5 text-blue-400">•</span>
                                The organization shall ... (e.g. The organization has...)
                            </li>
                            <li class="flex items-start gap-1.5">
                                <span class="mt-0.5 text-blue-400">•</span>
                                Be policy-safe: include clause reference (e.g., ISO 9001:2015 clause...)
                            </li>
                            <li class="flex items-start gap-1.5">
                                <span class="mt-0.5 text-blue-400">•</span>
                                Be concise and audit-ready
                            </li>
                        </ul>
                    </div>

                </div>
            </div>
        </form>

    </div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/form-submit.js') }}"></script>
<script>
    document.getElementById('formSubmit').addEventListener('submit', function() {
        document.getElementById('contentHidden').value = document.getElementById('contentEditor').innerHTML;
    });

    document.getElementById('attachments').addEventListener('change', function() {
        const fileList = document.getElementById('file-list');
        fileList.innerHTML = '';
        if (this.files.length > 0) {
            fileList.innerHTML = '<strong>Selected files:</strong><ul class="list-disc pl-4 mt-1">';
            Array.from(this.files).forEach(file => {
                fileList.innerHTML += `<li>${file.name} (${Math.round(file.size/1024)} KB)</li>`;
            });
            fileList.innerHTML += '</ul>';
        }
    });

    // Placeholder behavior
    const editor = document.getElementById('contentEditor');
    editor.addEventListener('focus', function() {
        if (this.innerHTML === '') this.classList.remove('text-gray-400');
    });

    // execCommand for toolbar
    function execCmd(cmd) {
        document.execCommand(cmd, false, null);
        editor.focus();
    }
    window.execCmd = execCmd;

    // Save as draft
    function saveDraft() {
        document.getElementById('statusField').value = 'draft';
        document.getElementById('contentHidden').value = document.getElementById('contentEditor').innerHTML;
        document.getElementById('formSubmit').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    }
    window.saveDraft = saveDraft;

    // Submit for approval -> under_review
    document.getElementById('submitBtn').addEventListener('click', function(e) {
        document.getElementById('statusField').value = 'under_review';
    });

    // Priority radio visual toggle
    document.querySelectorAll('.priority-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.priority-btn').forEach(function(btn) {
                btn.className = 'priority-btn text-center py-2 rounded-lg border text-xs font-medium transition-all bg-white text-gray-500 border-gray-200 hover:border-gray-400';
            });
            const val = this.value;
            const activeClass = val === 'low'
                ? 'bg-gray-600 text-white border-gray-600'
                : (val === 'medium' ? 'bg-yellow-500 text-white border-yellow-500' : 'bg-red-500 text-white border-red-500');
            this.nextElementSibling.className = 'priority-btn text-center py-2 rounded-lg border text-xs font-medium transition-all ' + activeClass;
        });
    });
</script>
@endpush
