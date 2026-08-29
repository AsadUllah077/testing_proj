@extends('layouts.auth')
@section('title', 'Create SOP')
@section('content')
<div class="mt-4 px-3">

    {{-- Header --}}
    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-[#0078C8] mb-1">Quality Management Protocol</p>
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900">Create SOP</h1>
            <a href="{{ route('qms.sop.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Back</a>
        </div>
    </div>

    {{-- Multi-step progress indicator --}}
    <div class="flex items-center gap-0 mb-6">
        @foreach(['Basic Info','Step Builder','Approval'] as $i => $step)
        <div class="flex items-center {{ $i < 2 ? 'flex-1' : '' }}">
            <div class="flex items-center gap-2">
                <div id="indicator-{{ $i }}" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all
                    {{ $i === 0 ? 'bg-[#0078C8] text-white' : 'bg-gray-200 text-gray-500' }}">
                    {{ $i + 1 }}
                </div>
                <span id="step-label-{{ $i }}" class="text-sm font-medium {{ $i === 0 ? 'text-[#0078C8]' : 'text-gray-400' }}">{{ $step }}</span>
            </div>
            @if($i < 2)
            <div class="flex-1 h-px bg-gray-200 mx-3"></div>
            @endif
        </div>
        @endforeach
    </div>

    <form id="formSubmit" method="POST"
          action_url="{{ route('qms.sop.store') }}"
          back_url="{{ route('qms.sop.index') }}">
        @csrf

        {{-- Step 1: Basic Info --}}
        <div id="step-0" class="space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Basic Information</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">SOP Title <span class="text-red-500">*</span></label>
                                <input type="text" name="title" required placeholder="e.g., Laptop Inspection & Quality Check"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                                <textarea name="description" rows="3" placeholder="Brief description of this SOP..."
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 resize-none"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Classification</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Department</label>
                                <select name="department" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                    <option value="">Select Department</option>
                                    @foreach(['QA','Technician','Sales','Operations','HR','Finance','IT'] as $d)
                                        <option value="{{ $d }}">{{ $d }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Linked Product</label>
                                <input type="text" name="linked_product" placeholder="e.g., Laptop, Mobile..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                    <option value="draft">Draft</option>
                                    <option value="review">Submit for Review</option>
                                    <option value="approved">Approved</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="goStep(1)"
                    class="px-6 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">
                    Next: Add Steps →
                </button>
            </div>
        </div>

        {{-- Step 2: Step Builder --}}
        <div id="step-1" class="hidden space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700">Step-by-Step Builder</h3>
                    <button type="button" onclick="addStep()"
                        class="px-3 py-1.5 bg-[#0078C8] text-white rounded-lg text-xs hover:bg-[#006bb0] flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Add Step
                    </button>
                </div>
                <div id="steps-container" class="space-y-3">
                    {{-- Steps rendered here by JS --}}
                </div>
                <div id="empty-steps" class="text-center py-10 text-gray-400 text-sm">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    No steps yet. Click "Add Step" to begin building.
                </div>
            </div>
            <div class="flex justify-between">
                <button type="button" onclick="goStep(0)" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">← Back</button>
                <button type="button" onclick="goStep(2)" class="px-6 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">Next: Approval →</button>
            </div>
        </div>

        {{-- Step 3: Approval --}}
        <div id="step-2" class="hidden space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Approval & Versioning</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Version</label>
                        <input type="text" name="version" value="1.0" placeholder="1.0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                        <p class="text-xs text-gray-400 mt-1">Auto-increments on major changes.</p>
                    </div>
                </div>
                {{-- Review summary --}}
                <div class="mt-5 p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <p class="text-xs font-semibold text-blue-700 mb-2 uppercase tracking-wider">Summary</p>
                    <p class="text-sm text-blue-700">Review all steps above before submitting. Once approved, this SOP will be visible to the assigned department.</p>
                </div>
            </div>
            <div class="flex justify-between">
                <button type="button" onclick="goStep(1)" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">← Back</button>
                <div class="flex gap-2">
                    <button type="submit" id="submitBtn" class="px-6 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">
                        Save SOP
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/form-submit.js') }}"></script>
<script>
let stepCount = 0;
const stepsContainer = document.getElementById('steps-container');
const emptySteps = document.getElementById('empty-steps');

function addStep() {
    emptySteps.classList.add('hidden');
    const idx = stepCount++;
    const div = document.createElement('div');
    div.id = `step-block-${idx}`;
    div.className = 'border border-gray-200 rounded-xl p-4 bg-gray-50 relative';
    div.innerHTML = `
        <div class="flex items-center gap-3 mb-3">
            <div class="w-7 h-7 rounded-full bg-[#0078C8] text-white flex items-center justify-center text-xs font-bold shrink-0">${idx + 1}</div>
            <input type="text" name="steps[${idx}][title]" placeholder="Step title..." required
                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
            <button type="button" onclick="removeStep(${idx})" class="text-red-400 hover:text-red-600 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <textarea name="steps[${idx}][description]" rows="2" placeholder="Describe what to do in this step..."
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 resize-none mb-2"></textarea>
        <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
            <input type="checkbox" name="steps[${idx}][has_checklist]" value="1" class="rounded border-gray-300 text-[#0078C8]">
            Include checklist checkbox for this step
        </label>
    `;
    stepsContainer.appendChild(div);
}

function removeStep(idx) {
    document.getElementById(`step-block-${idx}`)?.remove();
    if (stepsContainer.children.length === 0) emptySteps.classList.remove('hidden');
}
window.removeStep = removeStep;

// Add first step automatically
addStep();

// Multi-step navigation
function goStep(n) {
    [0,1,2].forEach(i => {
        document.getElementById(`step-${i}`).classList.add('hidden');
        document.getElementById(`indicator-${i}`).className = 'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all bg-gray-200 text-gray-500';
        document.getElementById(`step-label-${i}`).className = 'text-sm font-medium text-gray-400';
    });
    document.getElementById(`step-${n}`).classList.remove('hidden');
    document.getElementById(`indicator-${n}`).className = 'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all bg-[#0078C8] text-white';
    document.getElementById(`step-label-${n}`).className = 'text-sm font-medium text-[#0078C8]';
    // Mark completed steps
    for (let i = 0; i < n; i++) {
        document.getElementById(`indicator-${i}`).className = 'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all bg-green-500 text-white';
        document.getElementById(`step-label-${i}`).className = 'text-sm font-medium text-green-600';
    }
}
window.goStep = goStep;
</script>
@endpush
