@extends('layouts.auth')

@section('title', 'Task Details')

@section('content')
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-black">Task Details</h2>
                                <div class="flex items-center gap-4 mt-2">
                                    <span class="px-3 py-1 rounded-full text-sm font-medium {{ $task->status_color }}">
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        Created: {{ $task->created_at->format('M d, Y') }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('tasks.index') }}"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                    Back to List
                                </a>
                                <a href="{{ route('tasks.edit', $task->id) }}"
                                    class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                    Edit Task
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Card -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Column - Task Details -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Task Header Section -->
                            <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                                <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ $task->title }}</h1>

                                <!-- Task Info Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-gray-500">ASSIGNEE</p>
                                        <div class="flex items-center gap-2">
                                            @if ($task->assignedEmployees->count() > 0)
                                                @foreach ($task->assignedEmployees as $employee)
                                                    <div class="flex items-center gap-2">
                                                        <div
                                                            class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                            <span class="text-blue-600 text-sm font-medium">
                                                                {{ substr($employee->full_name, 0, 1) }}
                                                            </span>
                                                        </div>
                                                        <span
                                                            class="font-medium text-gray-900">{{ $employee->full_name }}</span>
                                                        @if ($employee->department)
                                                            <span
                                                                class="text-xs text-gray-500">({{ $employee->department->department }})</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="text-gray-400">Not assigned</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-gray-500">PRIORITY</p>
                                        <span
                                            class="px-3 py-1 rounded-full text-sm font-medium {{ $task->priority_color }}">
                                            {{ ucfirst($task->priority) }} Priority
                                        </span>
                                    </div>

                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-gray-500">DUE DATE</p>
                                        <div class="flex items-center gap-2">
                                            <p
                                                class="font-medium {{ $task->isOverdue() ? 'text-red-600' : 'text-gray-900' }}">
                                                {{ $task->deadline->format('M d, Y') }}
                                            </p>
                                            @if ($task->isOverdue())
                                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">
                                                    Overdue
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-500">{{ $task->deadline->format('h:i A') }}</p>
                                    </div>
                                </div>

                                <!-- Task Description -->
                                <div class="mb-6">
                                    <h3 class="text-sm font-medium text-gray-700 mb-2">TASK DESCRIPTION</h3>
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                        @if ($task->description)
                                            <p class="text-gray-700 whitespace-pre-line">{{ $task->description }}</p>
                                        @else
                                            <p class="text-gray-400 italic">No description provided.</p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Additional Info -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-gray-500">CATEGORY</p>
                                        <p class="font-medium text-gray-900">
                                            {{ $task->category === 'daily' ? 'Daily Task' : 'Weekly Task' }}
                                        </p>
                                    </div>

                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-gray-500">DEPARTMENT</p>
                                        <p class="font-medium text-gray-900">{{ $task->department->department ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Submitted Deliverables Section -->
                            <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Submitted Deliverables
                                        ({{ $task->deliverables->count() }})</h3>
                                    @if ($task->deliverables->count() > 0)
                                        <button onclick="toggleDeliverableList()"
                                            class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 font-medium flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Filter
                                        </button>
                                    @endif
                                </div>

                                @if ($task->deliverables->count() > 0)
                                    <!-- Filter Options -->
                                    <div id="deliverableFilter" class="mb-4 hidden">
                                        <div class="flex flex-wrap gap-2">
                                            <button onclick="filterDeliverables('all')"
                                                class="px-3 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">All</button>
                                            <button onclick="filterDeliverables('file')"
                                                class="px-3 py-1 text-xs bg-green-100 text-green-800 rounded-full">Files</button>
                                            <button onclick="filterDeliverables('text')"
                                                class="px-3 py-1 text-xs bg-purple-100 text-purple-800 rounded-full">Text</button>
                                        </div>
                                    </div>

                                    <div class="space-y-4" id="deliverablesList">
                                        @foreach ($task->deliverables as $deliverable)
                                            <div class="deliverable-item flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors"
                                                data-type="{{ $deliverable->type }}">
                                                <div class="flex items-center gap-3">
                                                    <!-- File Icon based on extension -->
                                                    <div
                                                        class="p-2 rounded-lg {{ $deliverable->type === 'file' ? 'bg-blue-100' : 'bg-green-100' }}">
                                                        @if ($deliverable->type === 'file')
                                                            @php
                                                                $extension = strtolower(
                                                                    pathinfo(
                                                                        $deliverable->file_name,
                                                                        PATHINFO_EXTENSION,
                                                                    ),
                                                                );
                                                                $icon = 'file';

                                                                if (in_array($extension, ['pdf'])) {
                                                                    $icon = 'pdf';
                                                                } elseif (in_array($extension, ['doc', 'docx'])) {
                                                                    $icon = 'doc';
                                                                } elseif (
                                                                    in_array($extension, ['xls', 'xlsx', 'csv'])
                                                                ) {
                                                                    $icon = 'excel';
                                                                } elseif (
                                                                    in_array($extension, [
                                                                        'jpg',
                                                                        'jpeg',
                                                                        'png',
                                                                        'gif',
                                                                        'bmp',
                                                                        'svg',
                                                                    ])
                                                                ) {
                                                                    $icon = 'image';
                                                                } elseif (in_array($extension, ['zip', 'rar'])) {
                                                                    $icon = 'archive';
                                                                } elseif (in_array($extension, ['ppt', 'pptx'])) {
                                                                    $icon = 'presentation';
                                                                }
                                                            @endphp

                                                            @switch($icon)
                                                                @case('pdf')
                                                                    <svg class="w-6 h-6 text-red-600" fill="currentColor"
                                                                        viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd"
                                                                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                @break

                                                                @case('doc')
                                                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor"
                                                                        viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd"
                                                                            d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 2a1 1 0 011-1h6a1 1 0 011 1v8a1 1 0 01-1 1H7a1 1 0 01-1-1V6z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                @break

                                                                @case('excel')
                                                                    <svg class="w-6 h-6 text-green-600" fill="currentColor"
                                                                        viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd"
                                                                            d="M3 4a1 1 0 011-1h12a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm1.5 2a.5.5 0 00-.5.5v1a.5.5 0 00.5.5h1a.5.5 0 00.5-.5v-1a.5.5 0 00-.5-.5h-1zm3 0a.5.5 0 00-.5.5v1a.5.5 0 00.5.5h1a.5.5 0 00.5-.5v-1a.5.5 0 00-.5-.5h-1zm4.5.5a.5.5 0 01.5-.5h1a.5.5 0 01.5.5v1a.5.5 0 01-.5.5h-1a.5.5 0 01-.5-.5v-1zm3.5-.5a.5.5 0 00-.5.5v1a.5.5 0 00.5.5h1a.5.5 0 00.5-.5v-1a.5.5 0 00-.5-.5h-1z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                @break

                                                                @case('image')
                                                                    <svg class="w-6 h-6 text-purple-600" fill="currentColor"
                                                                        viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd"
                                                                            d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                @break

                                                                @case('presentation')
                                                                    <svg class="w-6 h-6 text-orange-600" fill="currentColor"
                                                                        viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd"
                                                                            d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                @break

                                                                @default
                                                                    <svg class="w-6 h-6 text-gray-600" fill="currentColor"
                                                                        viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd"
                                                                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                            @endswitch
                                                        @else
                                                            <svg class="w-6 h-6 text-green-600" fill="currentColor"
                                                                viewBox="0 0 20 20">
                                                                <path
                                                                    d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                                                <path fill-rule="evenodd"
                                                                    d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-gray-900">{{ $deliverable->title }}</p>
                                                        <div class="flex items-center gap-2 text-sm text-gray-500">
                                                            @if ($deliverable->type === 'file')
                                                                <span
                                                                    class="text-xs px-2 py-1 bg-gray-100 rounded">{{ strtoupper($deliverable->file_extension ?? 'FILE') }}</span>
                                                                <span>•</span>
                                                                <span>{{ $deliverable->formatted_file_size }}</span>
                                                                <span>•</span>
                                                            @endif
                                                            <span>By
                                                                {{ $deliverable->submittedBy->full_name ?? 'Unknown' }}</span>
                                                            <span>•</span>
                                                            <span>{{ $deliverable->created_at->format('M d, Y h:i A') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    @if ($deliverable->type === 'file' && $deliverable->file_path)
                                                        <a href="{{ Storage::url($deliverable->file_path) }}"
                                                            target="_blank"
                                                            class="px-3 py-1 text-sm text-[#0078C8] hover:text-[#006bb0] font-medium flex items-center gap-1">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                                <path fill-rule="evenodd"
                                                                    d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            View
                                                        </a>
                                                        <a href="{{ Storage::url($deliverable->file_path) }}"
                                                            download="{{ $deliverable->file_name }}"
                                                            class="px-3 py-1 text-sm text-green-600 hover:text-green-800 font-medium flex items-center gap-1">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            Download
                                                        </a>
                                                    @endif
                                                    @if ($deliverable->description)
                                                        <button
                                                            onclick="showDeliverableDescription({{ $deliverable->id }})"
                                                            class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 font-medium flex items-center gap-1">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            Details
                                                        </button>
                                                    @endif
                                                    <!-- Delete Button (only for submitter or admin) -->
                                                    @if (auth()->id() == $deliverable->submitted_by ||
                                                            auth()->user()->can('delete-deliverable') ||
                                                            auth()->id() == $task->added_by)
                                                        <button onclick="deleteDeliverable({{ $deliverable->id }})"
                                                            class="px-3 py-1 text-sm text-red-600 hover:text-red-800 font-medium flex items-center gap-1">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-8">
                                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-gray-500">No deliverables submitted yet.</p>
                                    </div>
                                @endif

                                <!-- Add Deliverable Form (Only for assigned users) -->
                                {{-- @if (auth()->user()->isAssignedToTask($task) || auth()->user()->can('add-deliverable')) --}}
                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <h4 class="text-md font-medium text-gray-900 mb-4">Add New Deliverable</h4>
                                    <form id="deliverableForm" class="space-y-4" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="task_id" value="{{ $task->id }}">

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Deliverable
                                                    Type *</label>
                                                <select name="type" id="deliverable_type" required
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                                    <option value="">Select Type</option>
                                                    <option value="file">File Upload</option>
                                                    <option value="text">Text/Description</option>
                                                </select>
                                                <div id="type_error" class="text-red-500 text-xs mt-1 hidden"></div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                                                <input type="text" name="title" required
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                                    placeholder="e.g., Audit Report Q3">
                                                <div id="title_error" class="text-red-500 text-xs mt-1 hidden"></div>
                                            </div>
                                        </div>

                                        <!-- File Upload Field (shown when type is file) -->
                                        <div id="file_upload_field" class="hidden space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload File
                                                *</label>
                                            <div class="flex items-center gap-3">
                                                <label for="deliverable_file" class="cursor-pointer">
                                                    <div
                                                        class="px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-[#0078C8] hover:bg-blue-50 transition-colors">
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-5 h-5 text-gray-500" fill="currentColor"
                                                                viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            <span class="text-sm font-medium text-gray-700">Choose
                                                                File</span>
                                                        </div>
                                                    </div>
                                                    <input type="file" name="file" id="deliverable_file"
                                                        class="hidden"
                                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.gif,.bmp,.svg,.zip,.rar,.txt,.ppt,.pptx">
                                                </label>
                                                <div class="text-sm text-gray-600">
                                                    <span id="file_name">No file chosen</span>
                                                </div>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <p>Max: 20MB. Allowed: PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, JPEG, PNG, GIF,
                                                    BMP, SVG, ZIP, RAR, TXT, PPT, PPTX</p>
                                            </div>
                                            <div id="file_error" class="text-red-500 text-xs mt-1 hidden"></div>
                                            <div id="file_progress" class="hidden">
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div id="progress_bar" class="bg-[#0078C8] h-2 rounded-full"
                                                        style="width: 0%"></div>
                                                </div>
                                                <div id="progress_text" class="text-xs text-gray-500 mt-1">0%</div>
                                            </div>
                                        </div>

                                        <!-- Text Description Field (shown when type is text) -->
                                        <div id="text_description_field" class="hidden">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Description
                                                *</label>
                                            <textarea name="description" rows="3"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                                placeholder="Enter your deliverable description..."></textarea>
                                            <div id="description_error" class="text-red-500 text-xs mt-1 hidden"></div>
                                        </div>

                                        <div class="flex justify-end gap-2 pt-2">
                                            <button type="button" onclick="resetDeliverableForm()"
                                                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                                Cancel
                                            </button>
                                            <button type="submit" id="submitDeliverableBtn"
                                                class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Submit Deliverable
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                {{-- @endif --}}
                            </div>
                        </div>

                        <!-- Right Column - Manager Feedback & Actions -->
                        <div class="space-y-6">
                            <!-- Manager Feedback Section -->
                            <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Manager Remarks & Decision</h3>

                                @if ($task->manager_remarks)
                                    <div class="mb-6">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Feedback Notes</h4>
                                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                            <p class="text-amber-700 whitespace-pre-line">{{ $task->manager_remarks }}</p>
                                        </div>
                                    </div>
                                @endif

                                <!-- Manager Actions (Only for managers) -->
                                @if (auth()->user()->can('manage-tasks') || auth()->user()->id == $task->added_by)
                                    <form id="managerFeedbackForm" class="space-y-4">
                                        @csrf
                                        <input type="hidden" name="task_id" value="{{ $task->id }}">

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Feedback
                                                Notes</label>
                                            <textarea name="manager_remarks" id="manager_remarks" rows="4"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                                placeholder="Enter your evaluation, notes for revision, or praise here...">{{ $task->manager_remarks }}</textarea>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            @if ($task->status !== 'completed')
                                                <button type="button" onclick="approveTask({{ $task->id }})"
                                                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    Approve Task
                                                </button>
                                            @endif

                                            @if ($task->status !== 'revision_requested')
                                                <button type="button" onclick="requestRevision({{ $task->id }})"
                                                    class="px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-md hover:bg-yellow-700 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                    Request Revision
                                                </button>
                                            @endif

                                            <button type="button" onclick="saveRemarksOnly({{ $task->id }})"
                                                class="px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Save Remarks Only
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <div class="text-center py-4">
                                        <svg class="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        <p class="text-gray-500">Awaiting manager feedback...</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Task Timeline -->
                            <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Task Timeline</h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Created</p>
                                            <p class="text-xs text-gray-500">{{ $task->created_at->format('M d, Y') }}</p>
                                        </div>
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Last Updated</p>
                                            <p class="text-xs text-gray-500">{{ $task->updated_at->format('M d, Y') }}</p>
                                        </div>
                                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Deadline</p>
                                            <p class="text-xs text-gray-500">{{ $task->deadline->format('M d, Y') }}</p>
                                        </div>
                                        <div
                                            class="w-8 h-8 rounded-full {{ $task->isOverdue() ? 'bg-red-100' : 'bg-purple-100' }} flex items-center justify-center">
                                            <svg class="w-4 h-4 {{ $task->isOverdue() ? 'text-red-600' : 'text-purple-600' }}"
                                                fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Completed At</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $task->completed_at?->format('M d, Y h:i A')  }}
                                            </p>

                                        </div>
                                        <div
                                            class="w-8 h-8 rounded-full {{ $task->isOverdue() ? 'bg-red-100' : 'bg-purple-100' }} flex items-center justify-center">
                                            <svg class="w-4 h-4 {{ $task->isOverdue() ? 'text-red-600' : 'text-purple-600' }}"
                                                fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Deliverable Description Modal - UPDATED -->
    <div id="deliverableModal" class="fixed inset-0 bg-black/50 transition-opacity hidden z-[99999]">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4" id="deliverableModalTitle">Deliverable Details</h3>
                    <div id="deliverableModalContent" class="text-gray-700">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="closeDeliverableModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0078C8] sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal - UPDATED -->
    <div id="deleteDeliverableModal" class="fixed inset-0 bg-black/50 transition-opacity hidden z-[99999]">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="deleteModalTitle">Delete
                                Deliverable</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" id="deleteModalMessage">Are you sure you want to delete
                                    this deliverable? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="confirmDeleteDeliverable()" id="confirmDeleteBtn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button type="button" onclick="closeDeleteModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0078C8] sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed top-4 right-4 z-[999999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>
@endsection

@push('scripts')
    <script>
        // Deliverable Type Toggle
        document.getElementById('deliverable_type').addEventListener('change', function() {
            const type = this.value;
            const fileField = document.getElementById('file_upload_field');
            const textField = document.getElementById('text_description_field');

            // Clear all error messages
            clearErrorMessages();

            if (type === 'file') {
                fileField.classList.remove('hidden');
                textField.classList.add('hidden');
                document.getElementById('deliverable_file').required = true;
            } else if (type === 'text') {
                fileField.classList.add('hidden');
                textField.classList.remove('hidden');
                document.getElementById('deliverable_file').required = false;
            } else {
                fileField.classList.add('hidden');
                textField.classList.add('hidden');
                document.getElementById('deliverable_file').required = false;
            }
        });

        // File input change handler
        document.getElementById('deliverable_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileNameSpan = document.getElementById('file_name');
            const fileError = document.getElementById('file_error');

            if (file) {
                // Check file size (20MB = 20 * 1024 * 1024 bytes)
                const maxSize = 20 * 1024 * 1024;
                if (file.size > maxSize) {
                    fileError.textContent = 'File size exceeds 20MB limit.';
                    fileError.classList.remove('hidden');
                    e.target.value = '';
                    fileNameSpan.textContent = 'No file chosen';
                    return;
                }

                // Check file type
                const allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/csv',
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/gif',
                    'image/bmp',
                    'image/svg+xml',
                    'application/zip',
                    'application/x-rar-compressed',
                    'text/plain',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation'
                ];

                const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'gif',
                    'bmp', 'svg', 'zip', 'rar', 'txt', 'ppt', 'pptx'
                ];
                const fileExtension = file.name.split('.').pop().toLowerCase();

                if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
                    fileError.textContent =
                        'File type not allowed. Please upload PDF, DOC, XLS, CSV, images, presentations, or archives.';
                    fileError.classList.remove('hidden');
                    e.target.value = '';
                    fileNameSpan.textContent = 'No file chosen';
                    return;
                }

                fileNameSpan.textContent = file.name;
                fileError.classList.add('hidden');
            } else {
                fileNameSpan.textContent = 'No file chosen';
            }
        });

        // Clear error messages
        function clearErrorMessages() {
            const errors = ['type_error', 'title_error', 'file_error', 'description_error'];
            errors.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.classList.add('hidden');
                    element.textContent = '';
                }
            });
        }

        // Submit Deliverable Form
        document.getElementById('deliverableForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitDeliverableBtn');
            const originalText = submitBtn.innerHTML;

            // Clear previous errors
            clearErrorMessages();

            // Basic validation
            const type = document.getElementById('deliverable_type').value;
            const title = document.querySelector('input[name="title"]').value;

            if (!type) {
                document.getElementById('type_error').textContent = 'Please select a deliverable type.';
                document.getElementById('type_error').classList.remove('hidden');
                return;
            }

            if (!title.trim()) {
                document.getElementById('title_error').textContent = 'Please enter a deliverable title.';
                document.getElementById('title_error').classList.remove('hidden');
                return;
            }

            if (type === 'file') {
                const fileInput = document.getElementById('deliverable_file');
                if (!fileInput.files.length) {
                    document.getElementById('file_error').textContent = 'Please select a file to upload.';
                    document.getElementById('file_error').classList.remove('hidden');
                    return;
                }
            }

            if (type === 'text') {
                const description = document.querySelector('textarea[name="description"]').value;
                if (!description.trim()) {
                    document.getElementById('description_error').textContent =
                        'Please enter a description for text deliverables.';
                    document.getElementById('description_error').classList.remove('hidden');
                    return;
                }
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Uploading...
    `;

            // Show progress bar for file uploads
            if (type === 'file') {
                document.getElementById('file_progress').classList.remove('hidden');
            }

            // Create XMLHttpRequest for progress tracking
            const xhr = new XMLHttpRequest();

            if (type === 'file') {
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        document.getElementById('progress_bar').style.width = percentComplete + '%';
                        document.getElementById('progress_text').textContent = Math.round(percentComplete) +
                            '%';
                    }
                });
            }

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    document.getElementById('file_progress').classList.add('hidden');

                    try {
                        const data = JSON.parse(xhr.responseText);

                        if (xhr.status === 201 && data.success) {
                            showToast(data.message, 'success');
                            resetDeliverableForm();
                            // Reload the page to show new deliverable
                            setTimeout(() => location.reload(), 1000);
                        } else if (xhr.status === 422) {
                            // Validation errors
                            const errors = data.errors;
                            Object.keys(errors).forEach(key => {
                                const errorElement = document.getElementById(key + '_error');
                                if (errorElement) {
                                    errorElement.textContent = errors[key][0];
                                    errorElement.classList.remove('hidden');
                                }
                            });
                            showToast('Please fix the validation errors', 'error');
                        } else if (xhr.status === 403) {
                            showToast(data.message || 'Unauthorized action', 'error');
                        } else {
                            showToast(data.message || 'Error submitting deliverable', 'error');
                        }
                    } catch (e) {
                        showToast('Network error. Please try again.', 'error');
                    }
                }
            };

            xhr.open('POST', '{{ route('tasks.deliverables.store') }}', true);
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
            xhr.send(formData);
        });

        function resetDeliverableForm() {
            document.getElementById('deliverableForm').reset();
            document.getElementById('file_upload_field').classList.add('hidden');
            document.getElementById('text_description_field').classList.add('hidden');
            document.getElementById('file_name').textContent = 'No file chosen';
            clearErrorMessages();
        }

        // Filter deliverables
        function toggleDeliverableList() {
            const filter = document.getElementById('deliverableFilter');
            filter.classList.toggle('hidden');
        }

        function filterDeliverables(type) {
            const items = document.querySelectorAll('.deliverable-item');
            items.forEach(item => {
                if (type === 'all' || item.dataset.type === type) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        }

        // Updated modal functions with better handling
        function showDeliverableDescription(deliverableId) {
            fetch(`/tasks/deliverables/${deliverableId}/details`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('deliverableModalTitle').textContent = data.data.title;
                        document.getElementById('deliverableModalContent').innerHTML = `
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Description</p>
                            <p class="mt-1 text-gray-900 whitespace-pre-line">${data.data.description || 'No description provided.'}</p>
                        </div>
                        ${data.data.file_name ? `
                            <div>
                                <p class="text-sm font-medium text-gray-500">File Information</p>
                                <div class="mt-1 space-y-2">
                                    <p class="text-gray-900"><span class="font-medium">File:</span> ${data.data.file_name}</p>
                                    <p class="text-gray-900"><span class="font-medium">Type:</span> ${data.data.type.toUpperCase()}</p>
                                    ${data.data.formatted_file_size ? `<p class="text-gray-900"><span class="font-medium">Size:</span> ${data.data.formatted_file_size}</p>` : ''}
                                </div>
                            </div>
                            ` : ''}
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <span>Submitted by: ${data.data.submitted_by}</span>
                            <span>•</span>
                            <span>${data.data.created_at}</span>
                        </div>
                    </div>
                `;
                        // Show modal
                        const modal = document.getElementById('deliverableModal');
                        modal.classList.remove('hidden');

                        // Add click outside to close
                        setTimeout(() => {
                            modal.onclick = function(e) {
                                if (e.target === modal) {
                                    closeDeliverableModal();
                                }
                            };
                        }, 100);
                    } else {
                        showToast('Error loading deliverable details', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error loading deliverable details', 'error');
                });
        }

        function closeDeliverableModal() {
            const modal = document.getElementById('deliverableModal');
            modal.classList.add('hidden');
            modal.onclick = null; // Remove event listener
        }

        // Delete deliverable functionality
        let deliverableToDelete = null;

        function deleteDeliverable(deliverableId) {
            deliverableToDelete = deliverableId;
            const modal = document.getElementById('deleteDeliverableModal');
            modal.classList.remove('hidden');

            // Add click outside to close
            setTimeout(() => {
                modal.onclick = function(e) {
                    if (e.target === modal) {
                        closeDeleteModal();
                    }
                };
            }, 100);
        }

        function closeDeleteModal() {
            deliverableToDelete = null;
            const modal = document.getElementById('deleteDeliverableModal');
            modal.classList.add('hidden');
            modal.onclick = null; // Remove event listener
        }

        function confirmDeleteDeliverable() {
            if (!deliverableToDelete) return;

            const deleteBtn = document.getElementById('confirmDeleteBtn');
            const originalText = deleteBtn.innerHTML;

            deleteBtn.disabled = true;
            deleteBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Deleting...
    `;

            fetch(`/tasks/deliverables/${deliverableToDelete}/delete`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        closeDeleteModal();
                        // Remove the deliverable from the list without reloading
                        const deliverableElement = document.querySelector(
                            `[onclick="deleteDeliverable(${deliverableToDelete})"]`);
                        if (deliverableElement) {
                            deliverableElement.closest('.deliverable-item').remove();
                        }
                        // Update the count
                        const countElement = document.querySelector('h3.text-lg.font-semibold.text-gray-900');
                        if (countElement) {
                            const currentCount = parseInt(countElement.textContent.match(/\((\d+)\)/)[1]) || 0;
                            countElement.textContent = countElement.textContent.replace(/\((\d+)\)/,
                                `(${currentCount - 1})`);
                        }
                    } else {
                        showToast(data.message || 'Error deleting deliverable', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error. Please try again.', 'error');
                })
                .finally(() => {
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = originalText;
                });
        }

        // Add Escape key support to close modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!document.getElementById('deliverableModal').classList.contains('hidden')) {
                    closeDeliverableModal();
                }
                if (!document.getElementById('deleteDeliverableModal').classList.contains('hidden')) {
                    closeDeleteModal();
                }
            }
        });

        // Manager Actions
        function approveTask(taskId) {
            if (confirm('Are you sure you want to approve this task?')) {
                submitManagerAction(taskId, 'approve');
            }
        }

        function requestRevision(taskId) {
            if (confirm('Are you sure you want to request a revision?')) {
                submitManagerAction(taskId, 'revision');
            }
        }

        function saveRemarksOnly(taskId) {
            submitManagerAction(taskId, 'remarks');
        }

        function submitManagerAction(taskId, action) {
            const remarks = document.getElementById('manager_remarks').value;
            const submitBtn = event.target;
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Processing...
    `;

            fetch(`/tasks/${taskId}/manager-action`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: action,
                        remarks: remarks
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message || 'Error processing request', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    showToast('Network error. Please try again.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        }

        // Toast functions
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const content = document.getElementById('toast-content');
            toast.classList.remove('hidden');
            content.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="mt-0.5">
                ${type === 'success'
                    ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
                    : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9l-3-3m0 0l3 3m-3-3v6m6-6v6m0 0l-3-3m3 3l-3-3" clip-rule="evenodd"/></svg>'
                }
            </div>
            <div class="flex-1 text-sm">${message}</div>
            <button class="text-gray-500 hover:text-gray-700" onclick="hideToast()">✕</button>
        </div>
    `;
            setTimeout(hideToast, 3000);
        }

        function hideToast() {
            document.getElementById('toast').classList.add('hidden');
        }
    </script>
@endpush
