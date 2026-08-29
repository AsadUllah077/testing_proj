@extends('layouts.auth')
@section('title')
    Dashboard
@endsection

@section('content')
    <div class=" mb-8">
        <div class="py-12">
            <div class=" mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- Header -->
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-2xl font-semibold text-gray-800">Create Company Information</h2>
                        <div class="flex gap-2">
                            <a href="{{ route('home.companies.index') }}"
                                class="px-4 py-1.5 bg-[#0078C8] text-white rounded hover:bg-[#006bb0]">
                                Back </a>
                        </div>
                    </div>

                    <form action="{{ route('home.companies.store') }}" method="POST" enctype="multipart/form-data"
                        class="p-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

                            {{-- Company Name --}}
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Company
                                    Name</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                                    required>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Country --}}
                            <div>
                                <label for="country_id" class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                                <select name="country_id" id="country_id"
                                    class="select2 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('country_id') border-red-500 @enderror"
                                    required>
                                    <option value="">Select Country</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->id }}"
                                            {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="state_id" class="block text-sm font-medium text-gray-700 mb-2">State</label>
                                <select name="state_id" id="state_id"
                                    class="select2 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('state_id') border-red-500 @enderror">
                                    <option value="">Select State</option>
                                </select>
                                @error('state_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>


                            {{-- Currency --}}
                            <div>
                                <label for="currency_id"
                                    class=" block text-sm font-medium text-gray-700 mb-2">Currency</label>
                                <select name="currency_id" id="currency_id"
                                    class="select2 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('currency_id') border-red-500 @enderror"
                                    required>
                                    <option value="">Select Currency</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ old('currency_id') == $currency->id ? 'selected' : '' }}>
                                            {{ $currency->name }} ({{ $currency->symbol }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('currency_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Phone --}}
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('phone') border-red-500 @enderror">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Mobile --}}
                            <div>
                                <label for="mobile" class="block text-sm font-medium text-gray-700 mb-2">Mobile</label>
                                <input type="text" name="mobile" id="mobile" value="{{ old('mobile') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('mobile') border-red-500 @enderror">
                                @error('mobile')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>

                        {{-- File Uploads Card --}}
                        <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 mb-6 group hover:border-[#0078C8] transition-colors">
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 uppercase tracking-wider">Company Media Files</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {{-- Logo --}}
                                <div>
                                    <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">Company Logo</label>
                                    <input type="file" name="logo" id="logo"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-[#0078C8] @error('logo') border-red-500 @enderror"
                                        accept="image/*">
                                    @error('logo')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Signature --}}
                                <div>
                                    <label for="signature" class="block text-sm font-medium text-gray-700 mb-2">Signature</label>
                                    <input type="file" name="signature" id="signature"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-[#0078C8] @error('signature') border-red-500 @enderror"
                                        accept="image/*">
                                    @error('signature')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Favicon --}}
                                <div>
                                    <label for="favicon" class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
                                    <input type="file" name="favicon" id="favicon"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-[#0078C8] @error('favicon') border-red-500 @enderror"
                                        accept="image/*,.ico">
                                    @error('favicon')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-6 py-2 bg-[#0078C8] text-white rounded hover:bg-[#006bb0] rounded-md  focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Save Company
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

            $('#country_id').on('change', function() {
                let countryId = $(this).val();
                let $stateSelect = $('#state_id');

                // Clear previous options
                $stateSelect.html('<option value="">Select State</option>');

                if (!countryId) {
                    return;
                }

                $.ajax({
                    url: '/home/companies/get-states/' +
                        countryId, // <-- Adjust your route path here
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $.each(response.states, function(index, state) {
                                $stateSelect.append(
                                    $('<option>', {
                                        value: state.id,
                                        text: state.name
                                    })
                                );
                            });
                        } else {
                            alert(response.message || 'Failed to load states.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        alert('Something went wrong while fetching states.');
                    }
                });
            });

        });
    </script>
@endpush
