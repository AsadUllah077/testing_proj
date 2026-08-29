@extends('layouts.auth')

@section('title', 'Employee Details')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <!-- Header -->
            <div class="mx-auto bg-white border border-gray-200 rounded-md  p-6 mb-6">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-4">
                        @if ($employee->profile_picture)
                            <img src="{{ asset('storage/' . $employee->profile_picture) }}" alt="{{ $employee->full_name }}"
                                class="w-20 h-20 rounded-full object-cover border-2 border-blue-100">
                        @else
                            <div class="w-16 h-18 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-2xl font-semibold text-blue-600">
                                    {{ substr($employee->full_name, 0, 1) }}
                                </span>
                            </div>
                        @endif
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">{{ $employee->full_name }}</h1>
                            <div class="flex items-center gap-4 mt-1">

                                <span class="text-sm text-gray-600">
                                    {{ $employee->designation ?? 'N/A' }}
                                </span>
                                @if($employee->employee_id)
                                    <span class="text-sm text-gray-400">|</span>
                                    <span class="text-sm font-medium text-[#0078C8] bg-blue-50 px-2 py-0.5 rounded">
                                        ID: {{ $employee->employee_id }}
                                    </span>
                                @endif

                            </div>
                            <div class="flex items-center gap-4 mt-1">

                                <span class="text-sm text-gray-600">
                                    {{ $employee->department->department ?? 'N/A' }}
                                </span>

                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ get_index_url('hrm.employee.index') }}"
                            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                            Back to List
                        </a>
                        <a href="{{ route('hrm.employee.edit', $employee->id) }}"
                            class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                            Edit Employee
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Personal Information Card -->
                    <div class="bg-white border border-gray-200 rounded-md  p-6">
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Personal Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Full Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->full_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Father Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->f_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Gender</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ ucfirst($employee->gender) ?? 'N/A' }}
                                    </p>
                                </div>

                            </div>

                            <div class="space-y-4">

                                <div>
                                    <label class="block text-sm font-medium text-gray-500">National ID / CNIC</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->national_id ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">DOB</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->date_of_birth ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Marital Status</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ ucfirst($employee->marital_status) ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Card -->
                    <div class="bg-white border border-gray-200 rounded-md  p-6">
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            Contact Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <h4 class="text-sm font-medium text-gray-700">Primary Contact</h4>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Phone Number</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->phone_number ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Email Address</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->email ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Residential Address</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->residential_address ?? 'N/A' }}</p>
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">City</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $employee->city ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">State</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $employee->state ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Country</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $employee->country ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h4 class="text-sm font-medium text-gray-700">Emergency Contact</h4>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Contact Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->emergency_contact_name ?? 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Phone Number</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->emergency_contact_phone ?? 'N/A' }}
                                    </p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Job Information Card -->
                    <div class="bg-white border border-gray-200 rounded-md  p-6">
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Organization Details
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">

                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Employment Type</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ $employee->employmentType->employee_type ?? 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Department</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ $employee->department->department ?? 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Designation</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->designation ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Salary</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->salary ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Date Of Joining</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->date_of_joining ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Company</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ $employee->company->name ?? 'N/A' }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Work Location</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $employee->work_location ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Working Hours</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        @if ($employee->start_time && $employee->end_time)
                                            {{ \Carbon\Carbon::parse($employee->start_time)->format('h:i A') }}
                                            –
                                            {{ \Carbon\Carbon::parse($employee->end_time)->format('h:i A') }}
                                        @else
                                            N/A
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Working Day</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        @if ($employee->working_day_from && $employee->working_day_to)
                                            {{ $employee->working_day_from }}
                                            –
                                            {{ $employee->working_day_to }}
                                        @else
                                            N/A
                                        @endif
                                    </p>
                                </div>

                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-100">
                            <label class="block text-sm font-medium text-gray-500">Job Description</label>
                            <p class="mt-2 text-sm text-gray-900 whitespace-pre-line">
                                {{ $employee->job_description ?? 'No job description provided.' }}
                            </p>
                        </div>
                    </div>
                    <!-- Documents Card -->
                    <div class="bg-white border border-gray-200 rounded-md p-6">
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Documents ({{ $employee->documents->count() }})
                        </h3>

                        @if ($employee->documents && $employee->documents->count() > 0)
                            <div class="space-y-4">
                                @foreach ($employee->documents as $document)
                                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <!-- Document Icon -->
                                                    <svg class="w-5 h-5 text-blue-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                    <h4 class="text-sm font-semibold text-gray-900">
                                                        {{ $document->document_name }}</h4>
                                                </div>

                                                <!-- Document Type Badge -->
                                                <div class="mb-2">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {{ ucfirst(str_replace('_', ' ', $document->document_type)) }}
                                                    </span>

                                                    <!-- Verification Badge -->
                                                    @if ($document->is_verified)
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor"
                                                                viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            Verified
                                                        </span>
                                                    @else
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">
                                                            Pending Verification
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- Description -->
                                                @if ($document->description)
                                                    <p class="text-sm text-gray-600 mb-2">{{ $document->description }}</p>
                                                @endif

                                                <!-- Document Details -->
                                                <div
                                                    class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-gray-500 mt-2">
                                                    <div>
                                                        <span class="font-medium">File:</span>
                                                        {{ $document->file_original_name }}
                                                    </div>
                                                    @if ($document->issue_date)
                                                        <div>
                                                            <span class="font-medium">Issue Date:</span>
                                                            {{ \Carbon\Carbon::parse($document->issue_date)->format('M d, Y') }}
                                                        </div>
                                                    @endif
                                                    @if ($document->expiry_date)
                                                        <div>
                                                            <span class="font-medium">Expiry Date:</span>
                                                            {{ \Carbon\Carbon::parse($document->expiry_date)->format('M d, Y') }}
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <span class="font-medium">Size:</span>
                                                        {{ round($document->file_size / 1024, 2) }} KB
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="flex items-center gap-2 ml-4">
                                                <a href="{{ Storage::url($document->file_path) }}" target="_blank"
                                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
                                                    title="View Document">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>
                                                <a href="{{ Storage::url($document->file_path) }}" download
                                                    class="p-2 text-green-600 hover:bg-green-50 rounded-md transition-colors"
                                                    title="Download Document">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500 text-sm">No documents uploaded yet.</p>
                                <a href="{{ route('hrm.employee.edit', $employee->id) }}"
                                    class="inline-block mt-4 px-4 py-2 bg-[#0078C8] text-white text-sm rounded-md ">
                                    Upload Documents
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right Column - Sidebar -->
                <div class="space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white border border-gray-200 rounded-md  p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Employee Status</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Status</label>
                                <div class="mt-2">
                                    <span
                                        class="inline-block w-fit px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    {{ $employee->status == 'active'
                                                        ? 'bg-green-100 text-green-800'
                                                        : ($employee->status == 'inactive'
                                                            ? 'bg-yellow-100 text-yellow-800'
                                                            : ($employee->status == 'terminated'
                                                                ? 'bg-red-100 text-red-800'
                                                                : 'bg-gray-100 text-gray-800')) }}">
                                        {{ ucfirst($employee->status) }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500">Created At</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $employee->created_at?->format('d M, Y h:i A') }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500">Last Updated</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $employee->updated_at?->format('d M, Y h:i A') }}
                                </p>
                            </div>
                        </div>
                    </div>



                    <!-- Additional Information Card -->
                    <div class="bg-white border border-gray-200 rounded-md  p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Reporting To</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    @if ($employee->reporting_to)
                                        {{ $employee->reporting_to }}
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500">Linked ERP User</label>

                                @if ($employee->linkedErpUser)
                                    <a href="{{ route('users.user.show', $employee->linkedErpUser->id) }}"
                                        class="mt-1 block text-sm text-[#0078C8] font-medium hover:underline">
                                        {{ $employee->linkedErpUser->first_name . ' ' . $employee->linkedErpUser->last_name }}
                                    </a>
                                @else
                                    <p class="mt-1 text-sm text-gray-900">Not Linked</p>
                                @endif
                            </div>

                        </div>
                    </div>

                    <!-- Issued Assets Card -->
                    <div class="bg-white border border-gray-200 rounded-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Issued Assets
                        </h3>

                        @if($employee->issuedProducts && $employee->issuedProducts->count() > 0)
                            <div class="space-y-4">
                                @foreach($employee->issuedProducts as $product)
                                    <div class="p-3 border border-gray-100 rounded-md hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-900">{{ $product->title }}</span>
                                            <span class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded">{{ $product->category->category_name ?? 'Asset' }}</span>
                                        </div>
                                        <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
                                            <span>ID: {{ $product->unique_id }}</span>
                                            <a href="javascript:void(0)" onclick="openProductModal({{ $product->id }})" class="text-[#0078C8] hover:underline">View Details</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-sm text-gray-500 text-center">No assets issued to this employee.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Attendance Calendar Card -->
                    <div class="bg-white border border-gray-200 rounded-md p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v14a2 2 0 002 2z" />
                                </svg>
                                Attendance - {{ now()->format('F Y') }}
                            </h3>
                        </div>

                        @php
                            $startOfMonth = now()->startOfMonth();
                            $endOfMonth = now()->endOfMonth();
                            $calendarStart = $startOfMonth->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                            $calendarEnd = $endOfMonth->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);
                            $date = $calendarStart->copy();
                        @endphp

                        <div class="grid grid-cols-7 gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden">
                            <!-- Weekday Headers -->
                            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                                <div class="bg-gray-50 py-2 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                                    {{ $day }}
                                </div>
                            @endforeach

                            <!-- Calendar Days -->
                            @while($date->lte($calendarEnd))
                                @php
                                    $dateStr = $date->format('Y-m-d');
                                    $attendance = $attendances[$dateStr] ?? null;
                                    $leave = $leaveDates[$dateStr] ?? null;
                                    $isCurrentMonth = $date->month === now()->month;
                                    $isToday = $date->isToday();
                                @endphp

                                <div class="relative min-h-[70px] p-2 {{ $isCurrentMonth ? 'bg-white' : 'bg-gray-50 text-gray-400' }} group">
                                    <!-- Day Number -->
                                    <span class="text-xs font-semibold {{ $isToday ? 'bg-blue-600 text-white w-6 h-6 flex items-center justify-center rounded-full' : 'text-gray-700' }}">
                                        {{ $date->day }}
                                    </span>

                                    <!-- Status Indicator -->
                                    <div class="mt-1 space-y-1">
                                        @if($attendance)
                                            <div class="flex flex-col">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-[4px] text-[9px] font-bold uppercase {{ $attendance->status === 'present' ? 'bg-green-100 text-green-700' : ($attendance->status === 'late' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700') }}">
                                                    {{ $attendance->status }}
                                                </span>
                                                <div class="flex flex-col text-[8px] text-gray-500 mt-0.5 font-medium">
                                                    @if($attendance->check_in)
                                                        <span class="flex items-center gap-0.5">
                                                            <div class="w-1 h-1 rounded-full bg-green-500"></div>
                                                            {{ \Carbon\Carbon::parse($attendance->check_in)->format('h:i A') }}
                                                        </span>
                                                    @endif
                                                    @if($attendance->check_out)
                                                        <span class="flex items-center gap-0.5">
                                                            <div class="w-1 h-1 rounded-full bg-red-400"></div>
                                                            {{ \Carbon\Carbon::parse($attendance->check_out)->format('h:i A') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif($leave)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-[4px] text-[9px] font-bold uppercase bg-purple-100 text-purple-700" title="{{ $leave->leaveType?->name }}">
                                                LEAVE
                                            </span>
                                        @elseif($date->isPast() && $isCurrentMonth && !$date->isSunday())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-[4px] text-[9px] font-bold uppercase bg-red-50 text-red-400">
                                                Absent
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @php $date->addDay(); @endphp
                            @endwhile
                        </div>

                        <!-- Legend -->
                        <div class="mt-4 flex flex-wrap gap-4 text-[10px] text-gray-500 border-t border-gray-100 pt-4 font-medium uppercase tracking-tight">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-[3px] bg-green-100 border border-green-200"></div>
                                Present
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-[3px] bg-yellow-100 border border-yellow-200"></div>
                                Late
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-[3px] bg-purple-100 border border-purple-200"></div>
                                Leave
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-[3px] bg-red-50 border border-red-100"></div>
                                Absent
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Product Detail Modal -->
    <div id="productModal" class="fixed inset-0 z-[1000] overflow-y-auto hidden" style="display: none;">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="closeProductModal()"></div>

        <!-- Modal Content Wrapper -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full overflow-hidden transform transition-all z-[1001]">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Product Specifications</h3>
                    <button onclick="closeProductModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div id="productDetailsContent" class="p-6">
                    <div class="flex justify-center py-12">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[#0078C8]"></div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button onclick="closeProductModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Check if there's a success message in the URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                showToast('success', urlParams.get('success'));
            }
        });

        function showToast(type, message) {
            const toast = $('#toast');
            const toastContent = $('#toast-content');

            if (type === 'success') {
                toast.css('border-left-color', '#10B981');
            } else {
                toast.css('border-left-color', '#EF4444');
            }

            toastContent.text(message);
            toast.removeClass('hidden').addClass('flex');

            setTimeout(() => {
                closeToast();
            }, 5000);
        }

        function closeToast() {
            $('#toast').addClass('hidden').removeClass('flex');
        }

        function openProductModal(id) {
            console.log('Requesting details for Product:', id);

            // Reset and show modal
            $('#productDetailsContent').html('<div class="flex justify-center py-12"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[#0078C8]"></div></div>');
            $('#productModal').fadeIn(200).removeClass('hidden');

            $.ajax({
                url: `/warehouse/products/${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response);
                    if(response.success) {
                        const product = response.product;
                        let specHtml = '';

                        if(product.specifications && product.specifications.length > 0) {
                            specHtml = `
                                <div class="mt-6">
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Technical Specifications</h4>
                                    <div class="space-y-2">
                                        ${product.specifications.map(spec => `
                                            <div class="flex justify-between items-center p-3 bg-blue-50/50 rounded-lg border border-blue-100/50">
                                                <span class="text-sm text-gray-600 font-medium">${spec.name || spec.key}</span>
                                                <span class="text-sm font-bold text-gray-900">${spec.value}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            `;
                        }

                        const html = `
                            <div class="space-y-6">
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Asset Title</label>
                                        <p class="text-sm font-bold text-gray-900">${product.title || 'N/A'}</p>
                                    </div>
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Unique ID</label>
                                        <p class="text-sm font-bold text-blue-600">${product.unique_id}</p>
                                    </div>
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Category</label>
                                        <p class="text-sm font-semibold text-gray-800">${product.category ? product.category.name : 'N/A'}</p>
                                    </div>
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Location</label>
                                        <p class="text-sm font-semibold text-gray-800">${product.warehouse_location ? (product.warehouse_location.name || product.warehouse_location.location) : 'N/A'}</p>
                                    </div>
                                </div>
                                ${specHtml}
                            </div>
                        `;
                        $('#productDetailsContent').html(html);
                    } else {
                        $('#productDetailsContent').html('<div class="text-center py-12"><p class="text-red-500 font-bold">Failed to load asset details.</p></div>');
                    }
                },
                error: function(xhr) {
                    $('#productDetailsContent').html(`<div class="text-center py-12"><p class="text-red-500 font-bold">Error: ${xhr.status} - Could not connect to server.</p></div>`);
                }
            });
        }

        function closeProductModal() {
            $('#productModal').fadeOut(200, function() {
                $(this).addClass('hidden');
            });
        }
    </script>
@endpush
