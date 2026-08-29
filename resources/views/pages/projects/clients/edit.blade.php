@extends('layouts.auth')
@section('title', 'Edit Client')

@section('content')
    <x-acccount.loader />
    <div class="mt-4 mx-auto px-3 max-w-12xl">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('project.clients.index') }}" class="hover:text-[#0078C8]">Clients</a>
            <span>/</span>
            <span class="text-gray-800">{{ $client->name }}</span>
            <span>/</span>
            <span class="text-gray-800">Edit</span>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Edit Client</h2>
            </div>

            <form id="formSubmit" method="post"
                  action_url="{{ route('project.clients.update', $client) }}"
                  back_url="{{ route('project.clients.index') }}"
                  class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <!-- Section: Basic Information -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-[#0078C8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        Basic Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Client / Company Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $client->name) }}" required placeholder="Enter name"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Type</label>
                            <select name="type" class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="company" {{ old('type', $client->type) === 'company' ? 'selected' : '' }}>Company</option>
                                <option value="individual" {{ old('type', $client->type) === 'individual' ? 'selected' : '' }}>Individual</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Industry</label>
                            <input type="text" name="industry" value="{{ old('industry', $client->industry) }}" placeholder="e.g. Technology, Healthcare"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Email</label>
                            <input type="email" name="email" value="{{ old('email', $client->email) }}" placeholder="client@example.com"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone', $client->phone) }}" placeholder="+1 234 567 8900"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Website</label>
                            <input type="text" name="website" value="{{ old('website', $client->website) }}" placeholder="https://example.com"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                            <select name="status" class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="active" {{ old('status', $client->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $client->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section: Primary Contact -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-[#0078C8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        Primary Contact
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Contact Person</label>
                            <input type="text" name="contact_person" value="{{ old('contact_person', $client->contact_person) }}" placeholder="Full name"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Contact Email</label>
                            <input type="email" name="contact_email" value="{{ old('contact_email', $client->contact_email) }}" placeholder="contact@example.com"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Contact Phone</label>
                            <input type="text" name="contact_phone" value="{{ old('contact_phone', $client->contact_phone) }}" placeholder="+1 234 567 8900"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                    </div>
                </div>

                <!-- Section: Address -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-[#0078C8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        Address
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Street Address</label>
                            <input type="text" name="address" value="{{ old('address', $client->address) }}" placeholder="123 Main Street"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">City</label>
                            <input type="text" name="city" value="{{ old('city', $client->city) }}" placeholder="City"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">State / Province</label>
                            <input type="text" name="state" value="{{ old('state', $client->state) }}" placeholder="State"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Country</label>
                            <input type="text" name="country" value="{{ old('country', $client->country) }}" placeholder="Country"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Postal Code</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code', $client->postal_code) }}" placeholder="00000"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                    </div>
                </div>

                <!-- Section: Notes -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-[#0078C8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        Notes
                    </h3>
                    <textarea name="notes" rows="3" placeholder="Additional notes about this client..."
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all resize-none">{{ old('notes', $client->notes) }}</textarea>
                </div>

                <!-- Actions -->
                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-2 border-t border-gray-100">
                    <a href="{{ route('project.clients.index') }}"
                        class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors text-center">
                        Cancel
                    </a>
                    <button type="submit" id="submitBtn"
                        class="px-5 py-2.5 bg-[#0078C8] text-white text-sm rounded-lg hover:bg-[#006bb0] transition-colors text-center">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
