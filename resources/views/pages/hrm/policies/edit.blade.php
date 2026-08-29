@extends('layouts.auth')

@section('title', 'Edit Policy')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3 max-w-4xl">
            <div class="overflow-hidden">
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal">Edit Policy</h2>
                        <p class="text-gray-600 text-sm mt-1">Update company policy details</p>
                    </div>
                    <div>
                        <a href="{{ route('hrm.policies.index') }}"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-xs font-normal">Back</a>
                    </div>
                </div>

                <form action="{{ route('hrm.policies.update', $policy->id) }}" method="post" enctype="multipart/form-data"
                    class="bg-white rounded-lg border border-gray-200 p-6">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                            <input type="text" name="title" value="{{ old('title', $policy->title) }}"
                                class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none" required>
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" rows="5"
                                class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">{{ old('description', $policy->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload File (Optional)</label>
                            <input type="file" name="file"
                                class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">
                            <p class="mt-1 text-xs text-gray-500">Allowed types: pdf, doc, docx, png, jpg, jpeg (Max 10MB)</p>
                            @error('file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            @if ($policy->file_path)
                                <div class="mt-3 p-3 bg-gray-50 border rounded-lg flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-file text-[#0078C8] text-lg"></i>
                                        <span class="text-xs text-gray-700 font-medium">Current File Attached</span>
                                    </div>
                                    <a href="{{ route('hrm.policies.download', $policy->id) }}"
                                        class="text-xs text-[#0078C8] hover:underline font-semibold flex items-center gap-1">
                                        <i class="fa-solid fa-file-arrow-down"></i> Download File
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('hrm.policies.index') }}"
                            class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 font-medium text-sm">Cancel</a>
                        <button type="submit"
                            class="px-6 py-2.5 bg-[#0078C8] border-2 border-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-all duration-200 font-medium text-sm">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
