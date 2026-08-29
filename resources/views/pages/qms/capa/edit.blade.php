@extends('layouts.auth')
@section('title', 'Edit CAPA')
@section('content')
<div class="mt-4 px-3">

    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-[#0078C8] mb-1">Quality Management Protocol</p>
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit CAPA</h1>
                <p class="text-sm text-gray-400 mt-0.5">{{ $capa->capa_number }}</p>
            </div>
            <a href="{{ route('qms.capa.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Back</a>
        </div>
    </div>

    <form id="formSubmit" method="POST"
          action_url="{{ route('qms.capa.update', $capa->id) }}"
          back_url="{{ route('qms.capa.index') }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 space-y-4">

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Issue Details</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Issue Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" value="{{ old('title', $capa->title) }}" required
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                            <textarea name="description" rows="3"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 resize-none">{{ old('description', $capa->description) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Root Cause Analysis</h3>
                    <textarea name="root_cause" rows="4"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 resize-none">{{ old('root_cause', $capa->root_cause) }}</textarea>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Link Entities</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Linked Product</label>
                            <input type="text" name="linked_product" value="{{ old('linked_product', $capa->linked_product) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Linked SOP</label>
                            <select name="linked_sop_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                <option value="">None</option>
                                @foreach($sops as $sop)
                                    <option value="{{ $sop->id }}" {{ old('linked_sop_id', $capa->linked_sop_id) == $sop->id ? 'selected' : '' }}>
                                        {{ $sop->sop_number }} — {{ $sop->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Resolution Notes</h3>
                    <textarea name="resolution_notes" rows="3"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100 resize-none">{{ old('resolution_notes', $capa->resolution_notes) }}</textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('qms.capa.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                    <button type="submit" id="submitBtn" class="px-5 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">Update CAPA</button>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Status & Assignment</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                @foreach(['open'=>'Open','in_progress'=>'In Progress','closed'=>'Closed'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ old('status', $capa->status) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Deadline</label>
                            <input class="date" type="date" name="deadline"
                                value="{{ old('deadline', $capa->deadline?->format('Y-m-d')) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                        </div>
                    </div>
                </div>

                {{-- Activity Timeline --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-4">Activity</h3>
                    <div class="space-y-3">
                        <div class="flex gap-3 text-sm">
                            <div class="w-2 h-2 rounded-full bg-blue-400 mt-1.5 shrink-0"></div>
                            <div>
                                <p class="text-gray-700 font-medium">Created</p>
                                <p class="text-xs text-gray-400">{{ $capa->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                        @if($capa->closed_at)
                        <div class="flex gap-3 text-sm">
                            <div class="w-2 h-2 rounded-full bg-green-400 mt-1.5 shrink-0"></div>
                            <div>
                                <p class="text-gray-700 font-medium">Closed</p>
                                <p class="text-xs text-gray-400">{{ $capa->closed_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/form-submit.js') }}"></script>
@endpush
