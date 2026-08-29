@extends('layouts.auth')

@section('title', 'Supplier Details')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                {{-- Page Header --}}
                <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-blue-50 text-[#0078C8] flex items-center justify-center font-bold text-lg">
                            {{ strtoupper(substr($supplier->company_name, 0, 2)) }}
                        </div>
                        <div>
                            <h1 class="text-gray-900 text-xl font-normal">{{ $supplier->company_name }}</h1>
                            <p class="text-xs text-gray-400 mt-0.5">Supplier ID: {{ $supplier->supplier_code }} · 
                                <span class="@if($supplier->status === 'Approved') text-green-500 @elseif($supplier->status === 'Under Review') text-amber-500 @else text-red-500 @endif font-bold">
                                    {{ $supplier->status }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-common.back-button url="{{ route('logistics.suppliers.v2.index') }}" />
                        <a href="{{ route('logistics.suppliers.v2.edit', $supplier->id) }}"
                            class="px-4 py-2 bg-white text-gray-700 border border-gray-300 text-xs font-medium rounded hover:bg-gray-50 flex items-center justify-center gap-2 transition-colors">
                            <i class="fa-solid fa-pen"></i> Edit
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Left Column: Details --}}
                    <div class="lg:col-span-2 space-y-6">
                        
                        {{-- Company Info --}}
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-sm font-bold text-gray-800">🏭 Company Details</h3>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-12">
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Company Name</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->company_name }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Trade Name</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->trade_name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Business Type</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->business_type }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Category</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->category }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Location</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->city }}, {{ $supplier->country }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Registration / NTN</label>
                                    <p class="text-sm text-gray-900 font-medium font-mono">{{ $supplier->registration_no }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">STRN No.</label>
                                    <p class="text-sm text-gray-900 font-medium font-mono">{{ $supplier->strn_no ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Established</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->year_established ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Annual Value (USD)</label>
                                    <p class="text-sm text-gray-900 font-medium">${{ number_format($supplier->annual_value, 2) }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Contacts --}}
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-sm font-bold text-gray-800">👤 Key Contacts</h3>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-12">
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Primary Contact</label>
                                    <p class="text-sm text-gray-900 font-bold">{{ $supplier->primary_contact }}</p>
                                    <p class="text-xs text-gray-500">{{ $supplier->designation }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Contact Info</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->email }}</p>
                                    <p class="text-xs text-gray-500">{{ $supplier->phone }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Finance Contact</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->finance_contact ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Logistics Contact</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->logistics_contact ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Compliance --}}
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-sm font-bold text-gray-800">📋 Compliance & Legal</h3>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-12">
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">AML Status</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->aml_status }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">PEP Declaration</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->pep_declared }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Chamber Membership</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->chamber_membership ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Export License</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->export_license ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Right Column: Commercial & Stats --}}
                    <div class="space-y-6">
                        
                        {{-- Commercial Terms --}}
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-sm font-bold text-gray-800">💰 Commercial Terms</h3>
                            </div>
                            <div class="p-6 space-y-6">
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Preferred Incoterms</label>
                                    <span class="inline-block px-2 py-1 bg-blue-50 text-[#0078C8] text-[10px] font-bold rounded">{{ $supplier->incoterms }}</span>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Payment Terms</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->payment_terms ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Currency</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->currency }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Credit Limit</label>
                                    <p class="text-lg font-bold text-gray-900">${{ number_format($supplier->credit_limit, 2) }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase tracking-wider text-gray-400 font-bold block mb-1">Lead Time</label>
                                    <p class="text-sm text-gray-900 font-medium">{{ $supplier->lead_time ?? 'N/A' }} Days</p>
                                </div>
                            </div>
                        </div>

                        {{-- Performance Score --}}
                        <div class="bg-[#0078C8] rounded-xl shadow-lg p-6 text-white">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-bold opacity-80">Supplier Score</h3>
                                <i class="fa-solid fa-chart-line opacity-80"></i>
                            </div>
                            <div class="text-4xl font-black mb-1">92/100</div>
                            <div class="text-[10px] font-bold uppercase tracking-widest opacity-80 mb-4">Grade A · Excellent</div>
                            <div class="w-full bg-white/20 rounded-full h-1.5 mb-2">
                                <div class="bg-white h-1.5 rounded-full" style="width: 92%"></div>
                            </div>
                            <p class="text-[10px] opacity-70 leading-relaxed">Based on shipment reliability, document accuracy, and payment history.</p>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
