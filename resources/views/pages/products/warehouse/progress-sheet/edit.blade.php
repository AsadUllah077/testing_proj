@extends('layouts.auth')

@section('title', 'Edit Progress Sheet Entry')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('warehouse.progress-sheet.index') }}" class="text-gray-600 hover:text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </a>
                            <h2 class="text-stone-950 text-xl font-normal">Edit Progress Sheet Entry</h2>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 relative w-full sm:w-auto">
                            <!-- Update Button -->
                            <button type="submit" form="formSubmit" id="submitBtn"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                UPDATE
                            </button>
                            <!-- Cancel Button -->
                            <a href="{{ route('warehouse.progress-sheet.index') }}"
                                class="px-4 text-center py-2 border border-gray-300 text-gray-700 text-xs font-normal rounded hover:bg-gray-50 flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                CANCEL
                            </a>
                        </div>
                    </div>

                    @if(session('error'))
                        <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Main Form Card -->
                    <div class="mt-4 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="p-6">
                            <form id="formSubmit"
                                action_url="{{ route('warehouse.progress-sheet.update', $sheet->id) }}"
                                back_url="{{ route('warehouse.progress-sheet.index') }}"
                                method="POST"
                                class="space-y-6">
                                @csrf
                                @method('PUT')

                                <!-- Row 1: Date & Name -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Date <span class="text-red-500">*</span>
                                        </label>
                                        <input class="date" type="date" name="date"
                                            value="{{ old('date', $sheet->date->format('Y-m-d')) }}" required
                                            class="date w-full py-2.5 px-4 border @error('date') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        @error('date')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Name <span class="text-red-500">*</span>
                                        </label>
                                        <select name="name" required
                                            class="select2 w-full py-2.5 px-4 border @error('name') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                            <option value="">Select employee</option>
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee->full_name }}"
                                                    {{ old('name', $sheet->name) == $employee->full_name ? 'selected' : '' }}>
                                                    {{ $employee->full_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('name')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Row 2: Working & Quantity -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Working <span class="text-red-500">*</span>
                                        </label>
                                        <textarea name="working" rows="4" required
                                            placeholder="Describe the work performed"
                                            class="w-full py-2.5 px-4 border @error('working') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all resize-none">{{ old('working', $sheet->working) }}</textarea>
                                        @error('working')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Quantity
                                            <span class="text-xs text-gray-400 font-normal">(e.g. 2/1/1 or //)</span>
                                        </label>
                                        <input type="text" name="quantity"
                                            value="{{ old('quantity', $sheet->quantity) }}"
                                            placeholder="e.g. 2/1/1 or //"
                                            class="w-full py-2.5 px-4 border @error('quantity') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        @error('quantity')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Row 3: Remarks -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                                    <textarea name="remarks" rows="3"
                                        placeholder="Any additional remarks or comments..."
                                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all resize-none">{{ old('remarks', $sheet->remarks) }}</textarea>
                                </div>

                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection
