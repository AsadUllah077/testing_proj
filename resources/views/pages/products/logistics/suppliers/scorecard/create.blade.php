@extends('layouts.auth')

@section('title', 'New Scorecard')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                {{-- Page Header --}}
                <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                    <div>
                        <h1 class="text-gray-900 text-xl font-normal">New Performance Scorecard</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Evaluate supplier performance across core KPIs</p>
                    </div>
                    <x-common.back-button url="{{ route('logistics.suppliers.scorecard.index') }}" />
                </div>

                <form method="POST" action="{{ route('logistics.suppliers.scorecard.store') }}"
                    id="formSubmit" class="space-y-4">
                    @csrf

                    {{-- Evaluation Details --}}
                    <x-form.form-section title="🎯 Performance Evaluation" :required="true">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                            <x-form.form-select name="supplier_id" label="Supplier" :required="true"
                                placeholder="Select supplier..." :options="$suppliers->pluck('company_name', 'id')->toArray()" :selected="old('supplier_id')" />

                            <x-form.form-input name="evaluation_period" label="Evaluation Period" :required="true"
                                placeholder="e.g. Q1 2026, Annual 2025" :value="old('evaluation_period')" />

                            <div class="space-y-4 md:col-span-2 border-t border-gray-100 pt-6">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Key Performance Indicators (0-100)</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <x-form.form-input name="quality_score" label="Quality & Accuracy" type="number" min="0" max="100"
                                        placeholder="0-100" :value="old('quality_score')" />
                                    
                                    <x-form.form-input name="delivery_otd" label="Delivery OTD (%)" type="number" min="0" max="100"
                                        placeholder="0-100" :value="old('delivery_otd')" />
                                    
                                    <x-form.form-input name="doc_compliance" label="Document Compliance" type="number" min="0" max="100"
                                        placeholder="0-100" :value="old('doc_compliance')" />
                                </div>
                            </div>

                            <div class="space-y-4 md:col-span-2 border-t border-gray-100 pt-6">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Qualitative Ratings (1-5 Stars)</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-form.form-select name="communication" label="Communication Responsiveness" :required="true"
                                        :options="[1=>'1 - Poor', 2=>'2 - Fair', 3=>'3 - Good', 4=>'4 - Very Good', 5=>'5 - Excellent']" :selected="old('communication', 5)" />
                                    
                                    <x-form.form-select name="price_competitiveness" label="Price Competitiveness" :required="true"
                                        :options="[1=>'1 - Poor', 2=>'2 - Fair', 3=>'3 - Good', 4=>'4 - Very Good', 5=>'5 - Excellent']" :selected="old('price_competitiveness', 5)" />
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <x-form.form-textarea name="remarks" label="Evaluator Remarks"
                                    placeholder="Provide detailed feedback on supplier's strengths and areas for improvement…"
                                    :value="old('remarks')" :rows="3" />
                            </div>
                        </div>
                    </x-form.form-section>

                    {{-- Actions --}}
                    <div class="bg-white rounded-lg border border-black/10 px-6 py-4 flex items-center justify-end gap-3">
                        <a href="{{ route('logistics.suppliers.scorecard.index') }}"
                            class="px-5 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-5 py-2 text-xs font-medium text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-colors flex items-center gap-2">
                            Finalize Scorecard <i class="fa-solid fa-check text-[10px]"></i>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
