@extends('layouts.auth')
@section('title', 'Edit SOP')
@section('content')
<div class="mt-4 px-3">

    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-[#0078C8] mb-1">Quality Management Protocol</p>
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit SOP</h1>
                <p class="text-sm text-gray-400 mt-0.5">{{ $sop->sop_number }}</p>
            </div>
            <a href="{{ route('qms.sop.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Back</a>
        </div>
    </div>

    <form id="formSubmit" method="POST"
          action_url="{{ route('qms.sop.update', $sop->id) }}"
          back_url="{{ route('qms.sop.index') }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Basic Information</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">SOP Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" value="{{ old('title', $sop->title) }}" required
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                            <textarea name="description" rows="3"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 resize-none">{{ old('description', $sop->description) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Steps --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700">Procedure Steps</h3>
                        <button type="button" onclick="addStep()"
                            class="px-3 py-1.5 bg-[#0078C8] text-white rounded-lg text-xs hover:bg-[#006bb0] flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add Step
                        </button>
                    </div>
                    <div id="steps-container" class="space-y-3"></div>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('qms.sop.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                    <button type="submit" id="submitBtn" class="px-5 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">Update SOP</button>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Settings</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Department</label>
                            <select name="department" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                <option value="">Select Department</option>
                                @foreach(['QA','Technician','Sales','Operations','HR','Finance','IT'] as $d)
                                    <option value="{{ $d }}" {{ old('department', $sop->department) === $d ? 'selected' : '' }}>{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Linked Product</label>
                            <input type="text" name="linked_product" value="{{ old('linked_product', $sop->linked_product) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                @foreach(['draft'=>'Draft','review'=>'Under Review','approved'=>'Approved','archived'=>'Archived'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ old('status', $sop->status) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Version</label>
                            <input type="text" name="version" value="{{ old('version', $sop->version) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                        </div>
                    </div>
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

function addStep(title = '', description = '', hasChecklist = false) {
    const idx = stepCount++;
    const div = document.createElement('div');
    div.id = `step-block-${idx}`;
    div.className = 'border border-gray-200 rounded-xl p-4 bg-gray-50 relative';
    div.innerHTML = `
        <div class="flex items-center gap-3 mb-3">
            <div class="w-7 h-7 rounded-full bg-[#0078C8] text-white flex items-center justify-center text-xs font-bold shrink-0">${idx + 1}</div>
            <input type="text" name="steps[${idx}][title]" value="${title}" placeholder="Step title..." required
                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
            <button type="button" onclick="this.closest('[id^=step-block]').remove()" class="text-red-400 hover:text-red-600 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <textarea name="steps[${idx}][description]" rows="2" placeholder="Description..."
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 resize-none mb-2">${description}</textarea>
        <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
            <input type="checkbox" name="steps[${idx}][has_checklist]" value="1" class="rounded border-gray-300 text-[#0078C8]" ${hasChecklist ? 'checked' : ''}>
            Include checklist checkbox
        </label>
    `;
    stepsContainer.appendChild(div);
}
window.addStep = () => addStep();

// Pre-fill existing steps
@foreach($sop->steps as $step)
    addStep(
        @json($step->title),
        @json($step->description ?? ''),
        {{ $step->has_checklist ? 'true' : 'false' }}
    );
@endforeach

if (stepsContainer.children.length === 0) addStep();
</script>
@endpush
