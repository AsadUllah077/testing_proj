@extends('layouts.auth')

@section('title', 'Edit User')

@section('content')
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold text-black">Edit User Information</h2>
                            <a href="javascript:void(0)" onclick="window.history.back(); return false;"
                                class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                Back
                            </a>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6">
                        <form method="POST" action="{{ route('users.user.update', $user->id) }}"
                            enctype="multipart/form-data" class="space-y-8">
                            @csrf
                            @method('PUT')

                            <!-- ================= BASIC DETAILS ================= -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-[#0078C8]">Basic
                                    Details</h3>
                                <div class="grid md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">First Name</label>
                                        <input type="text" name="first_name"
                                            value="{{ old('first_name', $user->first_name) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8]" />
                                        @error('first_name')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Last Name</label>
                                        <input type="text" name="last_name"
                                            value="{{ old('last_name', $user->last_name) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                        @error('last_name')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Email</label>
                                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                        @error('email')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Phone Number</label>
                                        <input type="text" name="phone_number"
                                            value="{{ old('phone_number', $user->phone_number) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                        @error('phone_number')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Select Companies</label>
                                        <select name="companies[]" multiple="multiple"
                                            class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                            @php
                                                $userCompanies = $user->companies->pluck('id')->toArray();
                                            @endphp
                                            @foreach ($companies as $company)
                                                <option value="{{ $company->id }}"
                                                    {{ in_array($company->id, old('companies', $userCompanies)) ? 'selected' : '' }}>
                                                    {{ $company->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('companies')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <input type="hidden" name="company_id" id="primary_company_id"
                                        value="{{ old('company_id', $user->company_id) }}">

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Profile Picture</label>
                                        <div class="flex items-center gap-4">
                                            <input type="file" name="profile_pic" accept="image/*"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />

                                            @if ($user->profile_pic)
                                                <img src="{{ asset($user->profile_pic) }}" alt="Profile"
                                                    class="w-12 h-12 rounded-full object-cover border">
                                            @endif
                                        </div>
                                        @error('profile_pic')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Designation</label>
                                        <input type="text" name="designation"
                                            value="{{ old('designation', $user->designation) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                        @error('designation')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Select Role</label>
                                        <select name="role_id"
                                            class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100">

                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}"
                                                    {{ isset($user) && $user->role_id == $role->id ? 'selected' : '' }}>
                                                    {{ $role->name }}
                                                </option>
                                            @endforeach

                                        </select>
                                        @error('companies')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- ================= LOGIN & SECURITY ================= -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-[#0078C8]">Login &
                                    Security</h3>
                                <div class="grid md:grid-cols-3 gap-4 items-start">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Username</label>
                                        <input type="text" name="username"
                                            value="{{ old('username', $user->username) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                        @error('username')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="relative">
                                        <label class="block text-sm text-gray-600 mb-1">Password
                                            <span class="text-xs text-gray-400">(leave blank to keep same)</span>
                                        </label>
                                        <div class="relative group">
                                            <input type="password" name="password" id="password_input"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100 pr-10" />
                                            <button type="button" id="toggle_password"
                                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-[#0078C8] transition-colors">
                                                <svg id="eye_open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <svg id="eye_closed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                                </svg>
                                            </button>
                                        </div>
                                        
                                        <!-- Password Rules -->
                                        <div class="mt-2 space-y-1">
                                            <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wider">Password Requirements:</p>
                                            <ul class="text-[11px] text-gray-400 space-y-0.5 list-disc list-inside px-1">
                                                <li>Minimum 8 characters</li>
                                                <li>At least one uppercase & one lowercase letter</li>
                                                <li>At least one number (0-9)</li>
                                                <li>At least one special character (!@#$%^&*)</li>
                                            </ul>
                                        </div>

                                        @error('password')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Status Toggle -->
                                    <div class="flex flex-col">
                                        <label class="block text-sm text-gray-600 mb-1">Status</label>

                                        <div class="flex items-center space-x-3">
                                            <!-- Hidden default value -->
                                            <input type="hidden" name="status" value="0">

                                            <label class="inline-flex items-center cursor-pointer">

                                                <!-- Toggle checkbox -->
                                                <input type="checkbox" name="status" value="1" class="sr-only peer"
                                                    {{ old('status', $user->status) ? 'checked' : '' }}>

                                                <!-- Track -->
                                                <div
                                                    class="w-11 h-6 bg-gray-300 rounded-full peer-checked:bg-[#0078C8] relative transition-colors">
                                                    <!-- Knob -->
                                                    <div
                                                        class="absolute left-1 right-1 top-1 w-4 h-4 bg-white rounded-full transition-transform transform {{ $user->status ? 'translate-x-5' : 'translate-x-0' }} ">
                                                    </div>
                                                </div>

                                                <!-- Label -->
                                                <span id="toggleStatus"
                                                    class="ml-2 text-sm font-medium text-gray-700 peer-checked:text-[#0078C8]">
                                                    {{ old('status', $user->status) ? 'Enabled' : 'Disabled' }}
                                                </span>

                                            </label>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                                <a href="{{ route('users.user.index') }}"
                                    class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff]">
                                    CANCEL
                                </a>
                                <button type="submit"
                                    class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                    UPDATE
                                </button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.querySelectorAll('input[type="checkbox"][name="status"]').forEach(cb => {
            cb.addEventListener('change', e => {
                const trackKnob = e.target.closest('label').querySelector('div > div');
                if (e.target.checked) {
                    trackKnob.style.transform = 'translateX(-20px)';
                    document.getElementById("toggleStatus").innerText = 'Enabled'
                } else {
                    trackKnob.style.transform = 'translateX(0px)';
                    document.getElementById("toggleStatus").innerText = 'Disabled'
                }
            });
        });

        $(document).ready(function() {
            // Password Visibility Toggle
            const passwordInput = document.getElementById('password_input');
            const toggleButton = document.getElementById('toggle_password');
            const eyeOpen = document.getElementById('eye_open');
            const eyeClosed = document.getElementById('eye_closed');

            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        eyeOpen.classList.add('hidden');
                        eyeClosed.classList.remove('hidden');
                    } else {
                        passwordInput.type = 'password';
                        eyeOpen.classList.remove('hidden');
                        eyeClosed.classList.add('hidden');
                    }
                });
            }

            $('select[name="companies[]"]').on('change', function() {
                const selected = $(this).val();
                if (selected && selected.length > 0) {
                    $('#primary_company_id').val(selected[0]);
                } else {
                    $('#primary_company_id').val('');
                }
            });
        });
    </script>
@endpush
