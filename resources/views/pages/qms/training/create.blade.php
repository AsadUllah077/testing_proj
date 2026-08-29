@extends('layouts.auth')
@section('title', 'Create Training')
@section('content')
<div class="mt-4 px-3">

    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-[#0078C8] mb-1">Quality Management Protocol</p>
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900">Create Training</h1>
            <a href="{{ route('qms.training.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Back</a>
        </div>
    </div>

    <form id="formSubmit" method="POST"
          action_url="{{ route('qms.training.store') }}"
          back_url="{{ route('qms.training.index') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Main --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- 1. Training Info --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-6 h-6 rounded-full bg-[#0078C8] text-white flex items-center justify-center text-xs font-bold">1</div>
                        <h3 class="text-sm font-semibold text-gray-700">Training Info</h3>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Training Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" required placeholder="e.g., Laptop QA Inspection Training"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                            <textarea name="description" rows="3" placeholder="What will employees learn from this training?"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 resize-none"></textarea>
                        </div>
                    </div>
                </div>

                {{-- 2. Link Resources --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-6 h-6 rounded-full bg-[#0078C8] text-white flex items-center justify-center text-xs font-bold">2</div>
                        <h3 class="text-sm font-semibold text-gray-700">Link Resources</h3>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Link SOP</label>
                            <select name="linked_sop_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                <option value="">Select SOP (optional)</option>
                                @foreach($sops as $sop)
                                    <option value="{{ $sop->id }}">{{ $sop->sop_number }} — {{ $sop->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Link Policy</label>
                            <select name="linked_policy_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                <option value="">Select Policy (optional)</option>
                                @foreach($policies as $policy)
                                    <option value="{{ $policy->id }}">{{ $policy->policy_number }} — {{ $policy->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- 4. Assessment --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-6 h-6 rounded-full bg-[#0078C8] text-white flex items-center justify-center text-xs font-bold">3</div>
                        <h3 class="text-sm font-semibold text-gray-700">Assessment & Certification</h3>
                    </div>
                    <div class="space-y-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="has_assessment" value="1"
                                class="rounded border-gray-300 text-[#0078C8]" id="hasAssessmentCheck">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Include Assessment / Quiz</p>
                                <p class="text-xs text-gray-500">Employees must pass an evaluation to complete this training.</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="certification_enabled" value="1"
                                class="rounded border-gray-300 text-[#0078C8]" id="certCheck"
                                onchange="document.getElementById('certCriteria').classList.toggle('hidden', !this.checked)">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Issue Certificate on Completion</p>
                                <p class="text-xs text-gray-500">Employees receive a certificate when they complete this training.</p>
                            </div>
                        </label>
                        <div id="certCriteria" class="hidden pl-7">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Certification Criteria</label>
                            <textarea name="certification_criteria" rows="2" placeholder="e.g., Must score at least 80% on assessment..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('qms.training.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                    <button type="submit" id="submitBtn" class="px-5 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">Create Training</button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Settings</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-teal-50 rounded-xl border border-teal-100 p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-xs font-semibold text-teal-700">Note</span>
                    </div>
                    <p class="text-xs text-teal-700">Assign employees after creating the training from the edit screen.</p>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/form-submit.js') }}"></script>
@endpush
