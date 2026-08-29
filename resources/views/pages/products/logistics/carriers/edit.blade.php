@extends('layouts.auth')

@section('title', 'Logistics Carriers')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                {{-- Page Header --}}
                <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                    <h1 class="text-gray-900 text-xl font-normal">Edit Carrier</h1>
                    <x-common.back-button url="{{ route('logistics.carriers.index') }}" />
                </div>
                <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                    <form method="POST" action_url="{{ route('logistics.carriers.update', $carrier->id) }}"
                        back_url="{{ route('logistics.carriers.index') }}" id="formSubmit" class="p-6 space-y-6"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- General Information --}}
                        <x-form.form-section title="General Information" :required="true">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                                <x-form.form-input name="legal_carrier_name" label="Legal Carrier Name"
                                    placeholder="e.g. Blue Horizon Logistics Ltd." :value="$carrier->legal_carrier_name" />

                                <x-form.form-select name="logistics_mode" label="Logistics Mode"
                                    placeholder="Select mode..." :options="[
                                        'inland_trucking' => 'Inland (Trucking)',
                                        'air_freight' => 'Air Freight',
                                        'ocean_freight' => 'Ocean Freight',
                                        'rail_freight' => 'Rail Freight',
                                        'multimodal' => 'Multimodal',
                                    ]" :selected="$carrier->logistics_mode" />

                                <x-form.form-input name="scac_iata_code" label="SCAC / IATA Code"
                                    placeholder="4-character alpha code" :value="$carrier->scac_iata_code" />
                            </div>
                        </x-form.form-section>

                        {{-- Compliance & Insurance --}}
                        <x-form.form-section title="Compliance & Insurance">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                                <x-form.form-input name="dot_number" label="DOT Number" placeholder="Enter 7-digit USDOT"
                                    :value="$carrier->dot_number" />

                                <x-form.form-input name="mc_authority" label="MC Authority" placeholder="MC-XXXXXX"
                                    :value="$carrier->mc_authority" />

                                <x-form.form-select name="safety_rating" label="Safety Rating"
                                    placeholder="Select rating..." :options="[
                                        'Satisfactory' => 'Satisfactory',
                                        'Conditional' => 'Conditional',
                                        'Unsatisfactory' => 'Unsatisfactory',
                                        'Not Rated' => 'Not Rated',
                                    ]" :selected="$carrier->safety_rating" />
                            </div>
                        </x-form.form-section>

                        {{-- Required Documentation --}}
                        <x-form.form-section title="Required Documentation">
                            <div class="p-6 flex flex-col gap-3">
                                <label class="text-slate-700 text-sm font-semibold">Liability Insurance</label>
                                <label for="liability_insurance"
                                    class="flex flex-col items-center justify-center gap-2 p-8 bg-slate-50 rounded-xl border-2 border-dashed border-slate-200 cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors">
                                    <div class="w-12 h-12 bg-white rounded-full shadow flex items-center justify-center">
                                        <svg class="w-5 h-6 text-slate-400" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </div>
                                    <span class="text-slate-600 text-base font-medium">Click to upload or drag and
                                        drop</span>
                                    <span class="text-slate-400 text-xs">PDF, JPG, PNG, MAX 5MB</span>
                                    <input id="liability_insurance" name="liability_insurance" type="file" class="hidden"
                                        accept=".pdf,.jpg,.jpeg,.png,.zip,.exe">
                                </label>

                                {{-- Uploaded File Preview --}}
                                <div id="filePreview"
                                    class="{{ $carrier->liability_insurance_path ? '' : 'hidden' }} flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                                    <svg class="w-4 h-5 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M7 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5H7Zm5 1.5L18.5 11H14a2 2 0 0 1-2-2V4.5Z" />
                                    </svg>
                                    <div class="flex-1 min-w-0">
                                        <p id="fileName" class="text-slate-700 text-sm font-medium truncate">
                                            {{ $carrier->liability_insurance_path ? 'Current Document (' . basename($carrier->liability_insurance_path) . ')' : '' }}
                                        </p>
                                        <p id="fileStatus"
                                            class="text-[10px] leading-4 {{ $carrier->liability_insurance_path ? 'text-green-600' : 'text-slate-400' }}">
                                            {{ $carrier->liability_insurance_path ? 'Uploaded' : '' }}
                                        </p>
                                        <div
                                            class="h-1 bg-slate-200 rounded-full mt-1 overflow-hidden {{ $carrier->liability_insurance_path ? 'hidden' : '' }}">
                                            <div id="progressBar" class="h-1 bg-blue-600 rounded-full" style="width: 0%">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" id="removeFile" class="p-1 text-slate-400 hover:text-slate-600">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </x-form.form-section>

                        <x-common.button url="{{ route('logistics.carriers.index') }}" type="submit" />
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('liability_insurance');
            const filePreview = document.getElementById('filePreview');
            const fileName = document.getElementById('fileName');
            const fileStatus = document.getElementById('fileStatus');
            const progressBar = document.getElementById('progressBar');
            const removeBtn = document.getElementById('removeFile');

            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    filePreview.classList.remove('hidden');
                    fileName.textContent = file.name;
                    const size = (file.size / 1024 / 1024).toFixed(2);
                    fileStatus.textContent = `${size} MB • Uploading...`;

                    progressBar.style.width = '0%';
                    progressBar.parentElement.classList.remove('hidden');
                    fileStatus.classList.remove('text-green-600');
                    fileStatus.classList.add('text-slate-400');

                    let width = 0;
                    const interval = setInterval(function() {
                        if (width >= 100) {
                            clearInterval(interval);
                            fileStatus.textContent = `${size} MB • Completed`;
                            fileStatus.classList.remove('text-slate-400');
                            fileStatus.classList.add('text-green-600');
                            progressBar.parentElement.classList.add('hidden');
                        } else {
                            width += 10;
                            progressBar.style.width = width + '%';
                        }
                    }, 100);
                }
            });

            removeBtn.addEventListener('click', function() {
                fileInput.value = '';
                filePreview.classList.add('hidden');
            });
        });
    </script>
@endpush
