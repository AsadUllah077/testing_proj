@extends('layouts.auth')

@section('title', 'Edit Employee')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Header -->
                <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-black">Edit Employee</h2>
                        <a href="{{ get_index_url('hrm.employee.index') }}"
                            class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                            Back to List
                        </a>
                    </div>
                </div>

                <!-- Form -->
                <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6">
                    <form class="space-y-8" method="post" action_url="{{ route('hrm.employee.update', $employee->id) }}"
                        back_url="{{ get_index_url('hrm.employee.index') }}" id="formSubmit" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Personal Information Section -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Personal Information
                            </h3>

                            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <!-- Full Name -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Full Name *</label>
                                    <input type="text" name="full_name" id="full_name"
                                        value="{{ old('full_name', $employee->full_name) }}"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                        placeholder="e.g. Johnathan Doe" required>
                                    <div id="full_name_error" class="text-red-500 text-xs mt-1"></div>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Father Name *</label>
                                    <input type="text" name="f_name" id="f_name"
                                        value="{{ old('f_name', $employee->f_name) }}"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                        placeholder="e.g. Johnathan Doe" required>
                                    <div id="f_name_error" class="text-red-500 text-xs mt-1"></div>
                                </div>

                                <!-- Gender -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Gender</label>
                                    <select name="gender" id="gender"
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-blue-100">
                                        <option value="">Select Gender</option>
                                        <option value="male"
                                            {{ old('gender', $employee->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                        <option value="female"
                                            {{ old('gender', $employee->gender) == 'female' ? 'selected' : '' }}>Female
                                        </option>
                                        <option value="other"
                                            {{ old('gender', $employee->gender) == 'other' ? 'selected' : '' }}>Other
                                        </option>
                                    </select>
                                </div>

                                <!-- Date of Birth -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Date of Birth</label>
                                    <input class="date" type="date" name="date_of_birth"
                                        value="{{ old('date_of_birth', $employee->date_of_birth) }}" id="date_of_birth"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]">
                                </div>

                                <!-- Marital Status -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Marital Status</label>
                                    <select name="marital_status" id="marital_status"
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-blue-100">
                                        <option value="">Select Status</option>
                                        <option value="single"
                                            {{ old('marital_status', $employee->marital_status) == 'single' ? 'selected' : '' }}>
                                            Single</option>
                                        <option value="married"
                                            {{ old('marital_status', $employee->marital_status) == 'married' ? 'selected' : '' }}>
                                            Married</option>
                                        <option value="divorced"
                                            {{ old('marital_status', $employee->marital_status) == 'divorced' ? 'selected' : '' }}>
                                            Divorced</option>
                                        <option value="widowed"
                                            {{ old('marital_status', $employee->marital_status) == 'widowed' ? 'selected' : '' }}>
                                            Widowed</option>
                                    </select>
                                </div>

                                <!-- National ID / CNIC -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">National ID / CNIC *</label>
                                    <input type="text" name="national_id" id="national_id"
                                        value="{{ old('national_id', $employee->national_id) }}"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                        placeholder="XXXXXXXXXXXXXX-X" required>
                                    <div id="national_id_error" class="text-red-500 text-xs mt-1"></div>
                                </div>

                                <!-- Profile Picture -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Profile Picture</label>

                                    {{-- Current profile picture preview --}}
                                    @if ($employee->profile_picture)
                                        <div class="mb-2">
                                            <img src="{{ Storage::url($employee->profile_picture) }}"
                                                alt="Current Profile Picture"
                                                class="h-20 w-20 rounded-full object-cover border">
                                            <div class="mt-2">
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" name="remove_profile_picture" value="1"
                                                        class="h-4 w-4 text-red-600">
                                                    <span class="ml-2 text-sm text-red-600">Remove profile picture</span>
                                                </label>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- File input with icon --}}
                                    <div class="relative">
                                        <input type="file" name="profile_picture" id="profile_picture"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 pr-10 text-sm
                      file:mr-4 file:py-2 file:px-4
                      file:rounded-full file:border-0
                      file:text-sm file:font-medium
                      file:bg-gray-100 file:text-gray-700
                      hover:file:bg-gray-200
                      focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                            accept="image/*">

                                        {{-- Upload icon --}}
                                        <span
                                            class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 7h2l1-2h12l1 2h2v13H3V7z" />
                                                <circle cx="12" cy="13" r="3" />
                                            </svg>
                                        </span>
                                    </div>

                                    <div class="text-xs text-gray-500 mt-1">Max size: 2MB. Formats: JPG, PNG, GIF</div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information Section -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                Contact Information
                            </h3>

                            <div class="grid md:grid-cols-2 gap-6">
                                <!-- Left Column -->
                                <div class="space-y-4">
                                    <h4 class="text-sm font-medium text-gray-700">Primary Contact</h4>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Phone Number</label>
                                        <input type="tel" name="phone_number" id="phone_number"
                                            value="{{ old('phone_number', $employee->phone_number) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                            placeholder="+1 (555) 000-0000">
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Email Address</label>
                                        <input type="email" name="email" id="email"
                                            value="{{ old('email', $employee->email) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                            placeholder="name@company.com">
                                        <div id="email_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Residential Address</label>
                                        <textarea name="residential_address" id="residential_address" rows="3"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                            placeholder="Apartment, studio, or floor">{{ old('residential_address', $employee->residential_address) }}</textarea>
                                    </div>

                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1">City</label>
                                            <input type="text" name="city" id="city"
                                                value="{{ old('city', $employee->city) }}"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]">
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1">State</label>
                                            <input type="text" name="state" id="state"
                                                value="{{ old('state', $employee->state) }}"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]">
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1">Country</label>
                                            <input type="text" name="country" id="country"
                                                value="{{ old('country', $employee->country) }}"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]">
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column - Emergency Contact -->
                                <div class="space-y-4">
                                    <h4 class="text-sm font-medium text-gray-700">EMERGENCY CONTACT</h4>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Contact Name</label>
                                        <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                                            value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                            placeholder="Full Name">
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Phone Number</label>
                                        <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone"
                                            value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                            placeholder="Contact Number">
                                    </div>


                                </div>
                            </div>
                        </div>

                        <!-- Job Information Section -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Organization Details
                            </h3>

                            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <!-- Employment Type -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Employment Type</label>
                                    <select name="employment_type_id" id="employment_type_id"
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-blue-100"
                                        required>
                                        <option value="">Select Type</option>
                                        @foreach ($employmentTypes as $type)
                                            <option value="{{ $type->id }}"
                                                {{ old('employment_type_id', $employee->employment_type_id) == $type->id ? 'selected' : '' }}>
                                                {{ $type->employee_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Reporting To -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Reporting To</label>
                                    <input type="text" name="reporting_to" id="reporting_to"
                                        value="{{ old('reporting_to', $employee->reporting_to) }}"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                        placeholder="e.g. Asad">
                                </div>

                                <!-- Department -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Department</label>
                                    <select name="department_id" id="department_id"
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-blue-100"
                                        required>
                                        <option value="">Select Department</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}"
                                                {{ old('department_id', $employee->department_id) == $department->id ? 'selected' : '' }}>
                                                {{ $department->department }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <!-- Date of Joining -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Date of Joining</label>
                                    <input class="date" type="date" name="date_of_joining"
                                        value="{{ old('date_of_joining', $employee->date_of_joining) }}"
                                        id="date_of_joining"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]">
                                </div>

                                <!-- Work Location -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Work Location</label>
                                    <input type="text" name="work_location" id="work_location"
                                        value="{{ old('work_location', $employee->work_location) }}"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                        placeholder="e.g. New York Office">
                                </div>

                                <!-- Designation -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Designation / Job Title</label>
                                    <input type="text" name="designation" id="designation"
                                        value="{{ old('designation', $employee->designation) }}"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                        placeholder="e.g. Senior Manager">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Salary</label>
                                    <input type="integer" name="salary" value="{{ old('salary', $employee->salary) }}"
                                        id="salary"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]">
                                </div>
                                <!-- Company -->
                                {{-- <div>
                                    <label class="block text-sm text-gray-600 mb-1">Company</label>
                                    <select name="company_id" id="company_id"
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-blue-100">
                                        <option value="">Select Company</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}"
                                                {{ old('company_id', $employee->company_id) == $company->id ? 'selected' : '' }}>
                                                {{ $company->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div> --}}

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Status</label>
                                    <select name="status" id="status"
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-blue-100">
                                        <option value="active"
                                            {{ old('status', $employee->status) == 'active' ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="inactive"
                                            {{ old('status', $employee->status) == 'inactive' ? 'selected' : '' }}>Inactive
                                        </option>
                                        <option value="terminated"
                                            {{ old('status', $employee->status) == 'terminated' ? 'selected' : '' }}>
                                            Terminated</option>
                                        <option value="resigned"
                                            {{ old('status', $employee->status) == 'resigned' ? 'selected' : '' }}>Resigned
                                        </option>
                                    </select>
                                </div>

                                <!-- Resign Date -->
                                <div id="resign_date_wrapper" class="{{ old('status', $employee->status) == 'resigned' ? '' : 'hidden' }}">
                                    <label class="block text-sm text-gray-600 mb-1">Resign Date <span class="text-red-500">*</span></label>
                                    <input type="date" name="resign_date" id="resign_date"
                                        value="{{ old('resign_date', $employee->resign_date) }}"
                                        {{ old('status', $employee->status) == 'resigned' ? 'required' : '' }}
                                        class="date w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]">
                                    <div id="resign_date_error" class="text-red-500 text-xs mt-1"></div>
                                </div>

                                <!-- Linked ERP User -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Linked ERP User (Optional)</label>
                                    <select name="linked_erp_user_id" id="linked_erp_user_id"
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-blue-100">
                                        <option value="">Select User Account</option>
                                        @foreach ($erpUsers as $user)
                                            <option value="{{ $user->id }}"
                                                {{ old('linked_erp_user_id', $employee->linked_erp_user_id) == $user->id ? 'selected' : '' }}>
                                                {{ $user->first_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- App Role -->
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">App Role (Optional)</label>
                                    <select name="role_id" id="role_id"
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-blue-100">
                                        <option value="">— Not using app —</option>
                                        @foreach ($apiRoles as $role)
                                            <option value="{{ $role->id }}"
                                                {{ old('role_id', $employee->role_id) == $role->id ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Job Description -->
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label class="block text-sm text-gray-600 mb-1">Job Description</label>
                                    <textarea name="job_description" id="job_description" rows="4"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                        placeholder="Enter details about the role and responsibilities...">{{ old('job_description', $employee->job_description) }}</textarea>
                                </div>

                                <!-- Working Hours -->
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Working Hours</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <!-- Start Time -->
                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1">Start Time</label>
                                            <input type="time" name="start_time" id="start_time"
                                                value="{{ old('start_time', $employee->start_time) }}"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]">
                                        </div>
                                        <!-- End Time -->
                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1">End Time</label>
                                            <input type="time" name="end_time" id="end_time"
                                                value="{{ old('end_time', $employee->end_time) }}"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]">
                                        </div>
                                    </div>
                                </div>

                                <div class="md:col-span-2 lg:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Working Days</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1"
                                                for="working_day_from">From</label>
                                            <select name="working_day_from" id="working_day_from"
                                                class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]">
                                                <option value="">Select Day</option>
                                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                                    <option value="{{ $day }}" @selected(old('working_day_from', $employee->working_day_from ?? '') == $day)>
                                                        {{ $day }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1"
                                                for="working_day_to">To</label>
                                            <select name="working_day_to" id="working_day_to"
                                                class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]">
                                                <option value="">Select Day</option>
                                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                                    <option value="{{ $day }}" @selected(old('working_day_to', $employee->working_day_to ?? '') == $day)>
                                                        {{ $day }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Existing Documents Section -->
                        @if ($employee->documents && $employee->documents->count() > 0)
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-6 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Existing Documents
                                </h3>

                                <div class="space-y-4">
                                    @foreach ($employee->documents as $document)
                                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                            <div class="flex justify-between items-start mb-4">
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-700">
                                                        {{ $document->document_name }}</h4>
                                                    <p class="text-xs text-gray-500 mt-1">{{ $document->document_type }} •
                                                        {{ $document->file_original_name }}</p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ Storage::url($document->file_path) }}" target="_blank"
                                                        class="text-[#0078C8] hover:text-blue-700">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </a>
                                                    <button type="button" onclick="deleteDocument({{ $document->id }})"
                                                        class="text-red-600 hover:text-red-800">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            {{-- @if ($document->description) --}}
                                            {{-- <p class="text-sm text-gray-600 mb-2">{{ $document->description }}</p> --}}
                                            {{-- @endif --}}
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-gray-500">
                                                {{-- @if ($document->issue_date)
                                        <div>Issue: {{ \Carbon\Carbon::parse($document->issue_date)->format('M d, Y') }}</div>
                                        @endif
                                        @if ($document->expiry_date)
                                        <div>Expiry: {{ \Carbon\Carbon::parse($document->expiry_date)->format('M d, Y') }}</div>
                                        @endif
                                        <div>Size: {{ round($document->file_size / 1024) }} KB</div> --}}
                                                <div>
                                                    Verified:
                                                    @if ($document->is_verified)
                                                        <span class="text-green-600">✓ Yes</span>
                                                    @else
                                                        <span class="text-red-600">✗ No</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- New Documents Section -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6 flex items-center gap-2">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Add New Documents
                            </h3>

                            <div id="documents-container" class="space-y-4">
                                <!-- Document template (hidden) -->
                                <div id="document-template" class="hidden">
                                    <div class="document-item border border-gray-200 rounded-lg p-4 bg-gray-50">
                                        <div class="flex justify-between items-start mb-4">
                                            <h4 class="text-sm font-medium text-gray-700">New Document <span
                                                    class="document-index">1</span></h4>
                                            <button type="button"
                                                class="remove-document text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <!-- Document Type -->
                                            <div>
                                                <label class="block text-sm text-gray-600 mb-1">Document Type *</label>
                                                <select name="documents[0][document_type]"
                                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]">
                                                    <option value="">Select Type</option>
                                                    <option value="cv_resume">CV/Resume</option>
                                                    <option value="id_card">ID Card</option>
                                                    <option value="passport">Passport</option>
                                                    <option value="driving_license">Driving License</option>
                                                    <option value="educational_certificate">Educational Certificate
                                                    </option>
                                                    <option value="experience_certificate">Experience Certificate</option>
                                                    <option value="salary_slip">Salary Slip</option>
                                                    <option value="appointment_letter">Appointment Letter</option>
                                                    <option value="contract">Contract</option>
                                                    <option value="nda">NDA Agreement</option>
                                                    <option value="medical_certificate">Medical Certificate</option>
                                                    <option value="bank_details">Bank Details</option>
                                                    <option value="tax_document">Tax Document</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>

                                            <!-- Document Name -->
                                            <div>
                                                <label class="block text-sm text-gray-600 mb-1">Document Name *</label>
                                                <input type="text" name="documents[0][document_name]"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                                    placeholder="e.g., Passport Copy">
                                            </div>

                                            <!-- File Upload -->
                                            <div>
                                                <label class="block text-sm text-gray-600 mb-1">File *</label>
                                                <div class="relative">
                                                    <input type="file" name="documents[0][file]"
                                                        class="w-full border border-gray-300 rounded-md px-3 py-2 pr-10 text-sm
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-full file:border-0
                                          file:text-sm file:font-medium
                                          file:bg-gray-100 file:text-gray-700
                                          hover:file:bg-gray-200
                                          focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                                                    <span
                                                        class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">Max: 5MB. Formats: PDF, DOC, DOCX,
                                                    JPG, PNG, TXT</div>
                                            </div>

                                            <!-- Description -->
                                            {{-- <div class="lg:col-span-2">
                                                <label class="block text-sm text-gray-600 mb-1">Description</label>
                                                <textarea name="documents[0][description]" rows="2"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8]"
                                                    placeholder="Brief description about this document"></textarea>
                                            </div> --}}

                                            <!-- Issue Date -->
                                            {{-- <div>
                                                <label class="block text-sm text-gray-600 mb-1">Issue Date</label>
                                                <input type="date" name="documents[0][issue_date]"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8] date">
                                            </div> --}}

                                            <!-- Expiry Date -->
                                            {{-- <div>
                                                <label class="block text-sm text-gray-600 mb-1">Expiry Date</label>
                                                <input type="date" name="documents[0][expiry_date]"
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none focus:ring focus:ring-[#0078C8] date">
                                            </div> --}}

                                            <!-- Verification Status -->
                                            <div>
                                                <label class="block text-sm text-gray-600 mb-1">Verification Status</label>
                                                <div class="flex items-center mt-2">
                                                    <input type="checkbox" name="documents[0][is_verified]"
                                                        value="1"
                                                        class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded">
                                                    <label class="ml-2 text-sm text-gray-600">Verified</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add Document Button -->
                                <div class="flex justify-end">
                                    <button type="button" id="add-document"
                                        class="px-4 py-2 border border-dashed border-[#0078C8] text-[#0078C8] rounded-md hover:bg-blue-50 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add New Document
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                            <a href="{{ get_index_url('hrm.employee.index') }}"
                                class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff]">
                                CANCEL
                            </a>
                            <button type="submit" id="submitBtn"
                                class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                UPDATE EMPLOYEE
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Resign date show/hide
            function toggleResignDate(status) {
                const wrapper = document.getElementById('resign_date_wrapper');
                const input = document.getElementById('resign_date');
                if (status === 'resigned') {
                    wrapper.classList.remove('hidden');
                    input.setAttribute('required', 'required');
                } else {
                    wrapper.classList.add('hidden');
                    input.removeAttribute('required');
                }
            }
            $('#status').on('change', function() {
                toggleResignDate($(this).val());
            });

            // Document management
            let documentCount = 0;

            // Function to add new document field
            function addDocumentField() {
                const template = document.getElementById('document-template');
                const container = document.getElementById('documents-container');
                const addButton = document.getElementById('add-document').parentNode;

                // Create new document element
                const newDocument = template.cloneNode(true);
                newDocument.classList.remove('hidden');
                newDocument.id = '';

                // Update all input names with new index
                const inputs = newDocument.querySelectorAll('[name]');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    const newName = name.replace('[0]', `[${documentCount}]`);
                    input.setAttribute('name', newName);

                    // Reset values
                    if (input.type === 'text' || input.type === 'textarea') {
                        input.value = '';
                    } else if (input.type === 'select-one') {
                        input.selectedIndex = 0;
                    } else if (input.type === 'file') {
                        input.value = '';
                    } else if (input.type === 'checkbox') {
                        input.checked = false;
                    } else if (input.type === 'date') {
                        input.value = '';
                    }
                });

                // Update document index text
                const indexSpan = newDocument.querySelector('.document-index');
                if (indexSpan) {
                    indexSpan.textContent = documentCount + 1;
                }

                // Insert before the add button
                container.insertBefore(newDocument, addButton);

                // Add remove functionality
                const removeButton = newDocument.querySelector('.remove-document');
                removeButton.addEventListener('click', function() {
                    newDocument.remove();
                    updateDocumentIndices();
                });

                documentCount++;
            }

            // Function to update document indices after removal
            function updateDocumentIndices() {
                const documents = document.querySelectorAll('.document-item');
                documentCount = 0;

                documents.forEach((doc, index) => {
                    // Update display index
                    const indexSpan = doc.querySelector('.document-index');
                    if (indexSpan) {
                        indexSpan.textContent = index + 1;
                    }

                    // Update all input names
                    const inputs = doc.querySelectorAll('[name]');
                    inputs.forEach(input => {
                        const oldName = input.getAttribute('name');
                        const baseName = oldName.replace(/documents\[\d+\]/, 'documents[0]');
                        const newName = baseName.replace('[0]', `[${index}]`);
                        input.setAttribute('name', newName);
                    });

                    documentCount++;
                });
            }

            // Add document button event
            document.getElementById('add-document').addEventListener('click', addDocumentField);

            // Form submission
            $('#employeeForm').on('submit', function(e) {
                e.preventDefault();

                // Reset errors
                $('.text-red-500').text('');

                // Get form data
                let formData = new FormData(this);
                let submitBtn = $('#submitBtn');
                let originalText = submitBtn.text();

                // Show loading
                submitBtn.prop('disabled', true).text('Updating...');

                // Validate documents
                const documentInputs = document.querySelectorAll(
                    'input[name^="documents["][name$="][file]"]');
                let hasDocumentError = false;

                documentInputs.forEach((input, index) => {
                    if (input.files.length > 0) {
                        const file = input.files[0];
                        const maxSize = 5 * 1024 * 1024; // 5MB
                        const allowedTypes = ['application/pdf', 'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/jpeg', 'image/jpg', 'image/png', 'text/plain'
                        ];

                        if (file.size > maxSize) {
                            showToast('error',
                                `Document ${index + 1}: File size must be less than 5MB`);
                            hasDocumentError = true;
                        }

                        if (!allowedTypes.includes(file.type)) {
                            showToast('error', `Document ${index + 1}: Invalid file type`);
                            hasDocumentError = true;
                        }
                    }
                });

                if (hasDocumentError) {
                    submitBtn.prop('disabled', false).text(originalText);
                    return;
                }

                // Submit via AJAX
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-HTTP-Method-Override': 'PUT'
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast('success', response.message);

                            // Redirect to index page after 1.5 seconds
                            setTimeout(function() {
                                window.location.href =
                                    "{{ get_index_url('hrm.employee.index') }}";
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        submitBtn.prop('disabled', false).text(originalText);

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            for (let field in errors) {
                                if (field.startsWith('documents.')) {
                                    // Handle document-specific errors
                                    const match = field.match(/documents\.(\d+)\.(.+)/);
                                    if (match) {
                                        const docIndex = parseInt(match[1]);
                                        const fieldName = match[2];
                                        const errorElement = $(
                                                `[name="documents[${docIndex}][${fieldName}]"]`)
                                            .closest('div').find('.text-red-500');
                                        if (errorElement.length === 0) {
                                            $(`[name="documents[${docIndex}][${fieldName}]"]`)
                                                .after(
                                                    `<div class="text-red-500 text-xs mt-1">${errors[field][0]}</div>`
                                                );
                                        } else {
                                            errorElement.text(errors[field][0]);
                                        }
                                    }
                                } else {
                                    $('#' + field + '_error').text(errors[field][0]);
                                }
                            }
                            showToast('error', 'Please fix the errors in the form.');
                        } else {
                            showToast('error', xhr.responseJSON?.message ||
                                'An error occurred. Please try again.');
                        }
                    }
                });
            });

            // Profile picture preview
            $('#profile_picture').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('.profile-pic-preview').remove();
                        $('#profile_picture').before(`
                            <div class="profile-pic-preview mb-2">
                                <img src="${e.target.result}"
                                     alt="New Profile Picture"
                                     class="h-20 w-20 rounded-full object-cover border">
                            </div>
                        `);
                    }
                    reader.readAsDataURL(file);
                }
            });
        });

        // Delete document function
        function deleteDocument(documentId) {
            if (confirm('Are you sure you want to delete this document?')) {
                $.ajax({
                    url: "{{ route('hrm.employee.document.destroy', ':id') }}".replace(':id', documentId),
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast('success', response.message);
                            // Reload page to update document list
                            setTimeout(() => location.reload(), 1000);
                        }
                    },
                    error: function(xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Error deleting document');
                    }
                });
            }
        }

        function showToast(type, message) {
            const toast = $('#toast');
            const toastContent = $('#toast-content');

            if (type === 'success') {
                toast.css('border-left', '4px solid #10B981');
            } else {
                toast.css('border-left', '4px solid #EF4444');
            }

            toastContent.text(message);
            toast.removeClass('hidden').addClass('flex');

            setTimeout(() => {
                toast.addClass('hidden').removeClass('flex');
            }, 5000);
        }
    </script>
@endpush
