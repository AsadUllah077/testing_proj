@extends('layouts.auth')

@section('title', 'Edit Organization Profile')

@section('content')
<div class="min-h-screen bg-gray-50/30 py-8">
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10 bg-white p-4 rounded-xl">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Edit Organization</h1>
                <p class="text-sm text-gray-500 font-medium mt-1">Update your company identification, locale, and branding assets.</p>
            </div>
            <a href="{{ route('account.organization-settings.company', $company->id) }}"
                class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all duration-300">
                <span class="material-symbols-outlined mr-2 text-lg">arrow_back</span>
                Cancel & Return
            </a>
        </div>

        <form action="{{ route('home.companies.update', $company->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT')
            <input type="hidden" name="from_organization" value="1">

            <!-- Card 1: Primary Information -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden ring-1 ring-black/[0.03]">
                <div class="px-8 py-6 border-b border-gray-50 bg-gradient-to-r from-gray-50/50 to-transparent">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center">
                        <span class="w-8 h-8 rounded-lg bg-[#0078C8]/10 text-[#0078C8] flex items-center justify-center mr-3">
                            <span class="material-symbols-outlined text-lg">business_center</span>
                        </span>
                        Primary Details
                    </h3>
                </div>
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6">
                    <div class="col-span-1 md:col-span-2">
                        <label for="name" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Organization Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $company->name) }}"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-4 focus:ring-[#0078C8]/10 focus:border-[#0078C8] outline-none transition-all font-semibold @error('name') border-red-500 @enderror" required>
                        @error('name') <p class="mt-1 text-xs text-red-600 font-bold tracking-tight">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="country_id" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Country</label>
                        <select name="country_id" id="country_id" class="select2 w-full" required>
                            <option value="">Select Region</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" {{ old('country_id', $company->country_id) == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="state_id" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">State / Province</label>
                        <select name="state_id" id="state_id" class="select2 w-full">
                            <option value="">Select Territory</option>
                            @if ($company->country && $company->state_id)
                                @foreach (\App\Models\State::where('country_id', $company->country_id)->get() as $state)
                                    <option value="{{ $state->id }}" {{ old('state_id', $company->state_id) == $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div>
                        <label for="currency_id" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Base Currency</label>
                        <select name="currency_id" id="currency_id" class="select2 w-full" required>
                            <option value="">Select Currency</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}" {{ old('currency_id', $company->currency_id) == $currency->id ? 'selected' : '' }}>
                                    {{ $currency->name }} ({{ $currency->symbol }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Card 2: Contact Information -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden ring-1 ring-black/[0.03]">
                <div class="px-8 py-6 border-b border-gray-50 bg-gradient-to-r from-gray-50/50 to-transparent">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center">
                        <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mr-3">
                            <span class="material-symbols-outlined text-lg">alternate_email</span>
                        </span>
                        Contact Channels
                    </h3>
                </div>
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6">
                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Primary Email</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-lg">mail</span>
                            <input type="email" name="email" id="email" value="{{ old('email', $company->email) }}"
                                class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-4 focus:ring-[#0078C8]/10 focus:border-[#0078C8] outline-none transition-all font-semibold">
                        </div>
                    </div>
                    <div>
                        <label for="phone" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Phone (Line)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-lg">call</span>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $company->phone) }}"
                                class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-4 focus:ring-[#0078C8]/10 focus:border-[#0078C8] outline-none transition-all font-semibold">
                        </div>
                    </div>
                    <div>
                        <label for="mobile" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Direct Mobile</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-lg">smartphone</span>
                            <input type="text" name="mobile" id="mobile" value="{{ old('mobile', $company->mobile) }}"
                                class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-4 focus:ring-[#0078C8]/10 focus:border-[#0078C8] outline-none transition-all font-semibold">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Visual Identity -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden ring-1 ring-black/[0.03]">
                    <div class="px-8 py-6 border-b border-gray-50">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Brand Logo</h3>
                    </div>
                    <div class="p-8">
                        <div class="flex flex-col items-center">
                            <div class="relative w-32 h-32 mb-6 group">
                                <div class="w-full h-full rounded-2xl border-2 border-dashed border-gray-200 flex items-center justify-center bg-gray-50 group-hover:bg-gray-100 transition-colors">
                                    @if($company->logo)
                                        <img src="{{ asset('storage/' . $company->logo) }}" id="logo_preview" class="max-w-[80%] max-h-[80%] object-contain">
                                    @else
                                        <span class="material-symbols-outlined text-gray-300 text-3xl" id="logo_icon">image</span>
                                    @endif
                                </div>
                            </div>
                            <input type="file" name="logo" id="logo" class="hidden" accept="image/*">
                            <label for="logo" class="px-5 py-2.5 bg-gray-900 text-white text-xs font-bold rounded-xl cursor-pointer hover:bg-black transition-all">
                                Upload New Logo
                            </label>
                            <p class="mt-4 text-[10px] text-gray-400 font-medium">Recommended: PNG or SVG, 512x512px</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden ring-1 ring-black/[0.03]">
                    <div class="px-8 py-6 border-b border-gray-50">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Authorized Signature</h3>
                    </div>
                    <div class="p-8">
                        <div class="flex flex-col items-center">
                            <div class="relative w-full h-32 mb-6 group">
                                <div class="w-full h-full rounded-2xl border-2 border-dashed border-gray-200 flex items-center justify-center bg-gray-50 group-hover:bg-gray-100 transition-colors">
                                    @if($company->signature)
                                        <img src="{{ asset('storage/' . $company->signature) }}" id="sig_preview" class="max-w-[80%] max-h-[80%] object-contain contrast-125">
                                    @else
                                        <span class="material-symbols-outlined text-gray-300 text-3xl" id="sig_icon">signature</span>
                                    @endif
                                </div>
                            </div>
                            <input type="file" name="signature" id="signature" class="hidden" accept="image/*">
                            <label for="signature" class="px-5 py-2.5 bg-gray-900 text-white text-xs font-bold rounded-xl cursor-pointer hover:bg-black transition-all">
                                Update Signature
                            </label>
                            <p class="mt-4 text-[10px] text-gray-400 font-medium">Signed copies for digital approval</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Toolbar -->
            <div class="bg-white/80 backdrop-blur-md sticky bottom-6 p-6 rounded-3xl shadow-2xl shadow-black/10 border border-gray-100 flex items-center justify-between z-10 transition-all duration-500 hover:shadow-black/20">
                <div class="hidden md:flex items-center text-gray-400">
                    <span class="material-symbols-outlined mr-2 text-xl">verified_user</span>
                    <span class="text-[11px] font-bold uppercase tracking-tight">Security-validated change request</span>
                </div>
                <button type="submit" class="w-full md:w-auto px-10 py-4 bg-[#0078C8] text-white text-sm font-extrabold rounded-2xl shadow-lg shadow-[#0078C8]/30 hover:bg-[#006bb0] hover:-translate-y-0.5 transition-all duration-300 active:scale-95">
                    Save Changes & Finalize
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    
    /* Select2 Skin Overrides for this page context */
    .select2-container--default .select2-selection--single {
        background-color: #f8f9fa !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 12px !important;
        height: 52px !important;
        padding-top: 10px !important;
        font-weight: 600 !important;
    }
    
    .select2-container--default .select2-selection--single:focus {
        background-color: #ffffff !important;
        border-color: #0078C8 !important;
        box-shadow: 0 0 0 4px rgba(0, 120, 200, 0.1) !important;
    }
</style>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#country_id').on('change', function() {
            let countryId = $(this).val();
            let $stateSelect = $('#state_id');
            $stateSelect.html('<option value="">Syncing Territories...</option>');

            if (!countryId) {
                $stateSelect.html('<option value="">Select Territory</option>');
                return;
            }

            $.ajax({
                url: '/home/companies/get-states/' + countryId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $stateSelect.html('<option value="">Select Territory</option>');
                    if (response.success) {
                        $.each(response.states, function(index, state) {
                            $stateSelect.append($('<option>', { value: state.id, text: state.name }));
                        });
                        const selectedState = "{{ old('state_id', $company->state_id) }}";
                        if (selectedState) $stateSelect.val(selectedState).trigger('change');
                    }
                }
            });
        });

        // Image Previews
        function setupPreview(inputId, imgId, iconId) {
            $(`#${inputId}`).on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(re) {
                        if ($(`#${imgId}`).length) {
                            $(`#${imgId}`).attr('src', re.target.result);
                        } else {
                            $(`#${iconId}`).parent().html(`<img src="${re.target.result}" id="${imgId}" class="max-w-[80%] max-h-[80%] object-contain">`);
                        }
                    }
                    reader.readAsDataURL(file);
                }
            });
        }

        setupPreview('logo', 'logo_preview', 'logo_icon');
        setupPreview('signature', 'sig_preview', 'sig_icon');
    });
</script>
@endpush
