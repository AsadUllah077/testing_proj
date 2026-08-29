@extends('layouts.auth')
@section('title', 'Create CAPA')
@section('content')
<div class="mt-4 px-3">

    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-[#0078C8] mb-1">Quality Management Protocol</p>
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900">Create CAPA</h1>
            <a href="{{ route('qms.capa.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Back</a>
        </div>
    </div>

    <form id="formSubmit" method="POST"
          action_url="{{ route('qms.capa.store') }}"
          back_url="{{ route('qms.capa.index') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Main --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- 1. Issue Details --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-6 h-6 rounded-full bg-orange-500 text-white flex items-center justify-center text-xs font-bold">1</div>
                        <h3 class="text-sm font-semibold text-gray-700">Issue Details</h3>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Issue Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" required placeholder="e.g., Defective component batch found in inspection"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                            <textarea name="description" rows="3" placeholder="Describe the issue in detail..."
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 resize-none"></textarea>
                        </div>
                    </div>
                </div>

                {{-- 2. Root Cause Analysis --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-6 h-6 rounded-full bg-orange-500 text-white flex items-center justify-center text-xs font-bold">2</div>
                        <h3 class="text-sm font-semibold text-gray-700">Root Cause Analysis</h3>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Root Cause</label>
                        <textarea name="root_cause" rows="4"
                            placeholder="Why did this issue occur? (e.g., 5-Why analysis: 1. Defect found because... 2. Because...)"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 resize-none"></textarea>
                        <p class="text-xs text-gray-400 mt-1">Consider using 5-Why analysis or Fishbone diagram findings.</p>
                    </div>
                </div>

                {{-- 3. Link Entities --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-6 h-6 rounded-full bg-orange-500 text-white flex items-center justify-center text-xs font-bold">3</div>
                        <h3 class="text-sm font-semibold text-gray-700">Link Entities</h3>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Linked Product</label>
                            <input type="text" name="linked_product" placeholder="e.g., Laptop, Mobile Phone..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Linked SOP</label>
                            <select name="linked_sop_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                <option value="">Select SOP (optional)</option>
                                @foreach($sops as $sop)
                                    <option value="{{ $sop->id }}">{{ $sop->sop_number }} — {{ $sop->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Resolution Notes --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Resolution Notes</h3>
                    <textarea name="resolution_notes" rows="3"
                        placeholder="Document corrective actions taken or planned..."
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 resize-none"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('qms.capa.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                    <button type="submit" id="submitBtn" class="px-5 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">Create CAPA</button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Assign Action</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Status <span class="text-red-500">*</span></label>
                            <select name="status" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Deadline</label>
                            <input type="date" name="deadline"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 date">
                        </div>
                    </div>
                </div>

                <div class="bg-orange-50 rounded-xl border border-orange-100 p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-xs font-semibold text-orange-700">CAPA Guidelines</span>
                    </div>
                    <ul class="space-y-1.5 text-xs text-orange-700">
                        <li>• Identify root cause before assigning action</li>
                        <li>• Set a realistic deadline for resolution</li>
                        <li>• Link to relevant SOP or product for traceability</li>
                        <li>• Document resolution steps once completed</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/form-submit.js') }}"></script>
@endpush
