@extends('layouts.auth')

@section('title', (isset($booking) ? 'Edit Booking' : 'Create Shipment') . ' - Inland Logistics')

@section('content')
    <div class="mt-4" id="shipmentApp">
        {{-- Add Carrier Modal --}}
        <div id="carrierModal"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
            style="display:none;">
            <div class="bg-white rounded-[2.5rem] w-full max-w-xl overflow-hidden shadow-2xl max-h-[90vh] overflow-y-auto"
                id="carrierModalContent">
                <div class="bg-blue-600 px-8 py-6 text-white relative">
                    <h3 class="text-xl font-black uppercase tracking-widest">Register New Carrier</h3>
                    <p class="text-white/70 text-[10px] font-bold mt-1">Add a missing carrier to the system — it will be
                        saved and auto-selected</p>
                    <button id="closeCarrierModal"
                        class="absolute right-6 top-6 w-8 h-8 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/30 transition-all">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="p-8 space-y-5">
                    {{-- Error display --}}
                    <div id="carrierModalError"
                        class="hidden bg-red-50 border border-red-200 text-red-700 text-xs font-bold px-4 py-3 rounded-xl">
                    </div>

                    <div class="grid grid-cols-1 gap-5">
                        {{-- Legal Carrier Name --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Legal Carrier
                                Name <span class="text-red-500">*</span></label>
                            <input type="text" id="nc_legal_carrier_name"
                                class="w-full py-3 px-4 bg-slate-50 border-2 border-slate-100 rounded-xl focus:border-blue-500 focus:ring-0 font-bold text-slate-900 transition-all"
                                placeholder="e.g. SWIFT LOGISTICS LLC">
                        </div>

                        {{-- Logistics Mode --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Logistics Mode
                                <span class="text-red-500">*</span></label>
                            <select id="nc_logistics_mode"
                                class="w-full py-3 px-4 bg-slate-50 border-2 border-slate-100 rounded-xl focus:border-blue-500 transition-all font-bold text-slate-700">
                                <option value="">— Select Mode —</option>
                                <option value="road_freight">Road Freight</option>
                                <option value="sea_freight">Sea Freight</option>
                                <option value="air_freight">Air Freight</option>
                                <option value="rail_freight">Rail Freight</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            {{-- SCAC / IATA Code --}}
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">SCAC / IATA
                                    Code</label>
                                <input type="text" id="nc_scac_iata_code"
                                    class="w-full py-3 px-4 bg-slate-50 border-2 border-slate-100 rounded-xl focus:border-blue-500 focus:ring-0 font-bold text-slate-900 transition-all uppercase"
                                    placeholder="e.g. UPSN">
                            </div>

                            {{-- DOT Number --}}
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">DOT
                                    Number</label>
                                <input type="text" id="nc_dot_number"
                                    class="w-full py-3 px-4 bg-slate-50 border-2 border-slate-100 rounded-xl focus:border-blue-500 focus:ring-0 font-bold text-slate-900 transition-all"
                                    placeholder="e.g. 1234567">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            {{-- MC Authority --}}
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">MC
                                    Authority</label>
                                <input type="text" id="nc_mc_authority"
                                    class="w-full py-3 px-4 bg-slate-50 border-2 border-slate-100 rounded-xl focus:border-blue-500 focus:ring-0 font-bold text-slate-900 transition-all"
                                    placeholder="e.g. MC-123456">
                            </div>

                            {{-- Safety Rating --}}
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Safety
                                    Rating</label>
                                <select id="nc_safety_rating"
                                    class="w-full py-3 px-4 bg-slate-50 border-2 border-slate-100 rounded-xl focus:border-blue-500 transition-all font-bold text-slate-700">
                                    <option value="">— Select —</option>
                                    <option value="Satisfactory">Satisfactory</option>
                                    <option value="Conditional">Conditional</option>
                                    <option value="Unsatisfactory">Unsatisfactory</option>
                                    <option value="Not Rated">Not Rated</option>
                                </select>
                            </div>
                        </div>

                        {{-- Liability Insurance --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Liability
                                Insurance Document</label>
                            <input type="file" id="nc_liability_insurance" accept=".pdf,.jpg,.jpeg,.png,.zip"
                                class="w-full py-3 px-4 bg-slate-50 border-2 border-slate-100 rounded-xl focus:border-blue-500 text-sm text-slate-600 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100">
                            <p class="text-[9px] text-slate-400 mt-1">Accepted: PDF, JPG, PNG, ZIP (max 10MB)</p>
                        </div>
                    </div>

                    <button type="button" id="createCarrierBtn"
                        class="w-full py-4 px-8 bg-blue-600 text-white rounded-2xl font-black uppercase tracking-widest shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all flex items-center justify-center gap-3 disabled:opacity-50">
                        <i class="fa-solid fa-plus-circle" id="createCarrierIcon"></i>
                        <span id="createCarrierLabel">Create & Select</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="mx-auto px-3 pb-20">
            {{-- Header --}}
            <div
                class="flex items-center justify-between mb-8 bg-white rounded-2xl border border-slate-200 px-6 py-5 shadow-sm">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-[#0078C8] rounded-xl flex items-center justify-center shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-file-invoice text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-slate-900 text-2xl font-black tracking-tight">
                            {{ isset($booking) ? 'Edit Inland Booking' : 'New Inland Booking' }}</h1>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] mt-1">CH Robinson Standard
                            Template</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hidden md:flex flex-col items-end mr-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Draft Progress</span>
                        <div class="w-48 h-1.5 bg-slate-100 rounded-full mt-1 overflow-hidden">
                            <div id="progressBar" class="h-full bg-[#0078C8] transition-all duration-500" style="width:25%">
                            </div>
                        </div>
                    </div>
                    <x-common.back-button url="{{ route('logistics.manage-booking.index.inbound') }}" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                {{-- Left: Navigation Steps --}}
                <div class="lg:col-span-3 space-y-4">
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm h-fit sticky top-24">
                        <nav id="stepsNav" class="space-y-2"></nav>

                        {{-- Summary Widget --}}
                        <div class="mt-8 pt-8 border-t border-slate-100 px-2">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Live Quote
                                Detail</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-500">Service</span>
                                    <span class="text-xs font-bold text-slate-900" id="summaryService">-</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-500">Total Items</span>
                                    <span class="text-xs font-bold text-slate-900" id="summaryItems">0</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-500">Estimated Class</span>
                                    <span class="text-xs font-bold text-[#0078C8]" id="summaryClass">-</span>
                                </div>
                                <div class="pt-3 border-t border-slate-100 flex justify-between items-center"
                                    id="summaryRateRow" style="display:none">
                                    <span class="text-xs font-black text-slate-900">Selected Rate</span>
                                    <span class="text-lg font-black text-[#0078C8]" id="summaryRate">$0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Form Content --}}
                <div class="lg:col-span-9">
                    <form id="shippingForm">
                        {{-- Step 1: Quotes --}}
                        <div id="step1Panel">
                            <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
                                <div
                                    class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <i class="fa-solid fa-magnifying-glass-chart text-[#0078C8]"></i>
                                        <h3 class="font-bold text-slate-800 uppercase tracking-widest text-sm">Step 1:
                                            Quote Details</h3>
                                    </div>
                                    <span
                                        class="px-3 py-1 bg-blue-100 text-[#0078C8] text-[10px] font-black rounded-full uppercase tracking-widest">Initial
                                        Quote</span>
                                </div>
                                <div class="p-8 space-y-8">
                                    {{-- Service Type --}}
                                    <div class="space-y-4">
                                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Select
                                            Service Type</label>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="serviceTypeGrid"></div>
                                    </div>

                                    {{-- Origin / Destination --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        {{-- Shipping From --}}
                                        <div class="space-y-5">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="w-6 h-6 rounded bg-slate-100 flex items-center justify-center text-slate-400">
                                                    <i class="fa-solid fa-location-dot text-[10px]"></i>
                                                </div>
                                                <label
                                                    class="text-xs font-black text-slate-900 uppercase tracking-widest">Shipping
                                                    From</label>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-1.5">
                                                    <label
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Country</label>
                                                    <select id="originCountry"
                                                        class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]">
                                                        <option value="US">United States</option>
                                                        <option value="CA">Canada</option>
                                                        <option value="MX">Mexico</option>
                                                    </select>
                                                </div>
                                                <div class="space-y-1.5">
                                                    <label
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Postal
                                                        Code</label>
                                                    <div class="relative">
                                                        <input type="text" id="originPostal" autocomplete="off"
                                                            class="w-full py-2 px-3 pr-8 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                            placeholder="90210">
                                                        <span id="originPostalLoading" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-[#0078C8]">
                                                            <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                                                        </span>
                                                        <div id="originPostalDropdown" class="hidden absolute z-50 left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden max-h-52 overflow-y-auto"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="space-y-1.5">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Company
                                                    Name</label>
                                                <input type="text" id="originCompany"
                                                    class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                    placeholder="Pickup Company">
                                            </div>
                                            <div class="space-y-1.5">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Address Line 1</label>
                                                <input type="text" id="originStreet"
                                                    class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                    placeholder="123 Main St">
                                            </div>
                                            <div class="space-y-1.5">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Address Line 2 <span class="text-slate-300 font-normal normal-case">(optional)</span></label>
                                                <input type="text" id="originStreet2"
                                                    class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                    placeholder="Suite, Floor, Unit…">
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-1.5">
                                                    <label
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">City</label>
                                                    <input type="text" id="originCity"
                                                        class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                        placeholder="City">
                                                </div>
                                                <div class="space-y-1.5">
                                                    <label
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">State</label>
                                                    <input type="text" id="originState"
                                                        class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                        placeholder="NY">
                                                </div>
                                            </div>
                                            <div class="space-y-3">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wider italic">Location
                                                    Type</label>
                                                <div class="flex flex-wrap gap-2" id="originTypeButtons"></div>
                                            </div>
                                        </div>

                                        {{-- Shipping To --}}
                                        <div class="space-y-5">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="w-6 h-6 rounded bg-slate-100 flex items-center justify-center text-slate-400 text-[10px]">
                                                    <i class="fa-solid fa-flag-checkered"></i>
                                                </div>
                                                <label
                                                    class="text-xs font-black text-slate-900 uppercase tracking-widest">Shipping
                                                    To</label>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-1.5">
                                                    <label
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Country</label>
                                                    <select id="destCountry"
                                                        class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]">
                                                        <option value="US">United States</option>
                                                        <option value="CA">Canada</option>
                                                        <option value="MX">Mexico</option>
                                                    </select>
                                                </div>
                                                <div class="space-y-1.5">
                                                    <label
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Postal
                                                        Code</label>
                                                    <div class="relative">
                                                        <input type="text" id="destPostal" autocomplete="off"
                                                            class="w-full py-2 px-3 pr-8 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                            placeholder="10001">
                                                        <span id="destPostalLoading" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-[#0078C8]">
                                                            <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                                                        </span>
                                                        <div id="destPostalDropdown" class="hidden absolute z-50 left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden max-h-52 overflow-y-auto"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="space-y-1.5">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Company
                                                    Name</label>
                                                <input type="text" id="destCompany"
                                                    class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                    placeholder="Delivery Company">
                                            </div>
                                            <div class="space-y-1.5">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Address Line 1</label>
                                                <input type="text" id="destStreet"
                                                    class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                    placeholder="456 Target St">
                                            </div>
                                            <div class="space-y-1.5">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Address Line 2 <span class="text-slate-300 font-normal normal-case">(optional)</span></label>
                                                <input type="text" id="destStreet2"
                                                    class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                    placeholder="Suite, Floor, Unit…">
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-1.5">
                                                    <label
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">City</label>
                                                    <input type="text" id="destCity"
                                                        class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                        placeholder="City">
                                                </div>
                                                <div class="space-y-1.5">
                                                    <label
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">State</label>
                                                    <input type="text" id="destState"
                                                        class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                        placeholder="CA">
                                                </div>
                                            </div>
                                            <div class="space-y-3">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wider italic">Location
                                                    Type</label>
                                                <select id="destType"
                                                    class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]">
                                                    <option value="Amazon Warehouse">Amazon Warehouse</option>
                                                    <option value="Business">Business</option>
                                                    <option value="Residential">Residential</option>
                                                    <option value="Cross-Dock">Cross-Dock</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Additional Services & Date --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-6 border-t border-slate-100">
                                        <div class="space-y-4">
                                            <label
                                                class="text-xs font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                                                <i class="fa-solid fa-handshake-angle text-[#0078C8]"></i>
                                                Value Added Services
                                            </label>
                                            <div class="grid grid-cols-1 gap-2">
                                                <label
                                                    class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors cursor-pointer">
                                                    <input type="checkbox" id="service_liftgate" value="Lift Gate"
                                                        class="rounded text-[#0078C8] focus:ring-[#0078C8]">
                                                    <span class="text-sm text-slate-700 font-medium">Lift Gate
                                                        (Pickup/Delivery)</span>
                                                </label>
                                                <label
                                                    class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors cursor-pointer">
                                                    <input type="checkbox" id="service_inside" value="Inside Access"
                                                        class="rounded text-[#0078C8] focus:ring-[#0078C8]">
                                                    <span class="text-sm text-slate-700 font-medium">Inside Access
                                                        (Pickup/Delivery)</span>
                                                </label>
                                                <label
                                                    class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors cursor-pointer">
                                                    <input type="checkbox" id="service_appt" value="Appointment Fee"
                                                        class="rounded text-[#0078C8] focus:ring-[#0078C8]">
                                                    <span class="text-sm text-slate-700 font-medium">Appointment Scheduling
                                                        Fee</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="space-y-4">
                                            <label
                                                class="text-xs font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                                                <i class="fa-solid fa-calendar-day text-[#0078C8]"></i>
                                                Booking Date
                                            </label>
                                            <div class="space-y-1.5">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Requested
                                                    Booking Date</label>
                                                <div class="relative">
                                                    <i
                                                        class="fa-regular fa-calendar absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                                    <input type="date" id="loadingDate"
                                                        value="{{ isset($booking) && $booking->loading_date ? $booking->loading_date->format('Y-m-d') : '' }}"
                                                        class="w-full py-2 px-3 text-sm rounded-2xl border-slate-200 bg-slate-50 focus:ring-[#0078C8] pl-11">
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <label
                                                    class="text-xs font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                                                    <i class="fa-solid fa-calendar-day text-[#0078C8]"></i>
                                                    Pick Date
                                                </label>
                                                <div class="space-y-1.5">
                                                    <label
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pick
                                                        Date</label>
                                                    <div class="relative">
                                                        <i
                                                            class="fa-regular fa-calendar absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                                        <input type="date" id="pickDate"
                                                            value="{{ isset($booking) && $booking->pick_date ? $booking->pick_date->format('Y-m-d') : '' }}"
                                                            class="w-full py-2 px-3 text-sm rounded-2xl border-slate-200 bg-slate-50 focus:ring-[#0078C8] pl-11">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="space-y-4">
                                            <label
                                                class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Estimated
                                                time of arrival</label>
                                            <input type="date" id="eta"
                                                value="{{ isset($booking) && $booking->eta ? $booking->eta->format('Y-m-d') : '' }}"
                                                class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Step 2: Items --}}
                        <div id="step2Panel" style="display:none">
                            <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
                                <div
                                    class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <i class="fa-solid fa-layer-group text-[#0078C8]"></i>
                                        <h3 class="font-bold text-slate-800 uppercase tracking-widest text-sm">Step 2:
                                            Shipping Items</h3>
                                    </div>
                                </div>
                                <div class="p-8 space-y-8">
                                    <div id="itemsContainer"></div>

                                    {{-- Add Another Item Button --}}
                                    <div class="flex flex-col items-center justify-center py-6">
                                        <button type="button" id="addItemBtn"
                                            class="flex flex-col items-center gap-3 group transition-all">
                                            <div
                                                class="w-12 h-12 rounded-full border-2 border-blue-500 flex items-center justify-center text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-all shadow-lg shadow-blue-100">
                                                <i class="fa-solid fa-plus text-xl"></i>
                                            </div>
                                            <span
                                                class="text-[10px] font-black text-blue-500 uppercase tracking-widest group-hover:text-blue-600">Add
                                                Another Item</span>
                                        </button>
                                    </div>

                                    {{-- Freight Class Section --}}
                                    <div
                                        class="pt-8 border-t border-slate-100 flex items-center justify-between bg-slate-50/30 -mx-8 -mb-8 p-8">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-12 h-12 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600">
                                                <i class="fa-solid fa-scale-balanced text-xl"></i>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                                    Calculated Freight Class</p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="text-2xl font-black text-slate-900"
                                                        id="freightClassDisplay">---</span>
                                                    <span
                                                        class="text-[10px] font-bold text-orange-500 bg-orange-50 px-2 py-0.5 rounded-full">AUTO</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-64 space-y-1.5">
                                            <label
                                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-right block">Manual
                                                Override</label>
                                            <div class="relative">
                                                <button type="button" id="freightClassBtn"
                                                    class="w-full py-2.5 px-4 bg-white border border-slate-200 rounded-xl flex items-center justify-between hover:border-slate-300 transition-all shadow-sm">
                                                    <div class="flex flex-col items-start">
                                                        <span class="text-xs font-black text-slate-900"
                                                            id="freightClassBtnText">Class ---</span>
                                                        <span
                                                            class="text-[9px] text-slate-400 font-medium leading-none mt-0.5">Custom
                                                            Override</span>
                                                    </div>
                                                    <i id="freightClassChevron"
                                                        class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200"></i>
                                                </button>
                                                <div id="freightClassDropdown"
                                                    class="absolute z-50 right-0 w-80 mb-2 bottom-full bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden py-2 max-h-80 overflow-y-auto"
                                                    style="display:none">
                                                    <div class="px-4 py-2 border-b border-slate-50 bg-slate-50/50">
                                                        <span
                                                            class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Select
                                                            Freight Class</span>
                                                    </div>
                                                    <div id="freightClassList"></div>
                                                </div>
                                                <div class="absolute -top-3 right-0">
                                                    <i class="fa-solid fa-pen text-[8px] text-slate-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Step 3: Carrier Selection --}}
                        <div id="step3Panel" style="display:none">
                            <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
                                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <i class="fa-solid fa-truck-fast text-[#0078C8]"></i>
                                        <h3 class="font-bold text-slate-800 uppercase tracking-widest text-sm">Step 3:
                                            Carrier Selection</h3>
                                    </div>
                                </div>
                                <div class="p-8 space-y-8 text-left min-h-[500px]">

                                    {{-- Source Inputs --}}
                                    <div class="space-y-4">
                                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                            <i class="fa-solid fa-people-arrows text-[#0078C8]"></i>
                                            Source
                                        </label>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="space-y-1.5">
                                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Second Party</label>
                                                <input type="text" id="secondParty"
                                                    value="{{ isset($booking) ? addslashes($booking->second_party) : '' }}"
                                                    class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                    placeholder="e.g. Freight Broker">
                                            </div>
                                            <div class="space-y-1.5">
                                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Third Party</label>
                                                <input type="text" id="thirdParty"
                                                    value="{{ isset($booking) ? addslashes($booking->third_party) : '' }}"
                                                    class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-[#0078C8]"
                                                    placeholder="e.g. Customs Broker">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-end">
                                        {{-- Carrier Search --}}
                                        <div class="space-y-4">
                                            <label
                                                class="text-xs font-bold text-slate-800 uppercase tracking-widest">Select
                                                Carrier *</label>
                                            <div class="relative" id="carrierSearchWrapper">
                                                <div class="relative group">
                                                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                                                    </div>
                                                    <input type="text" id="carrierSearch"
                                                        class="w-full pl-11 pr-4 py-3.5 text-sm rounded-2xl border-2 border-slate-100 focus:border-[#0078C8] focus:ring-0 transition-all font-bold"
                                                        placeholder="Search or enter carrier name...">
                                                </div>
                                                <div id="carrierDropdown"
                                                    class="absolute z-50 w-full mt-3 min-h-[300px] max-h-[300px] overflow-y-auto bg-white rounded-[2rem] shadow-[0_25px_60px_-15px_rgba(0,0,0,0.2)] border border-slate-100 py-3"
                                                    style="display:none">
                                                    <div class="px-2 pb-1" id="carrierList"></div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Rate Input --}}
                                        <div class="space-y-4">
                                            <label class="text-xs font-bold text-slate-800 uppercase tracking-widest">Enter
                                                Money (Rate) *</label>
                                            <div class="relative group">
                                                <div
                                                    class="absolute left-4 top-1/2 -translate-y-1/2 text-[#0078C8] font-black">
                                                    <i class="fa-solid fa-dollar-sign"></i>
                                                </div>
                                                <input type="number" id="carrierRate"
                                                    value="{{ isset($booking) && $booking->carrier_rate ? (float)$booking->carrier_rate : '' }}"
                                                    class="w-full pl-11 pr-4 py-3.5 text-2xl rounded-2xl border-2 border-slate-100 focus:border-[#0078C8] focus:ring-0 transition-all font-black text-slate-900"
                                                    placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Selected Carrier Card --}}
                                    <div id="selectedCarrierCard"
                                        class="p-6 rounded-3xl bg-blue-50/50 border-2 border-[#0078C8]/20 flex items-center justify-between"
                                        style="display:none">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-[#0078C8] shadow-sm">
                                                <i class="fa-solid fa-truck-fast text-xl"></i>
                                            </div>
                                            <div>
                                                <span
                                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Active
                                                    Selection</span>
                                                <h3 class="text-lg font-black text-slate-900" id="selectedCarrierName">
                                                </h3>
                                            </div>
                                        </div>
                                        <button type="button" id="clearCarrierBtn"
                                            class="text-[10px] font-black text-red-500 uppercase tracking-widest hover:text-red-700">
                                            Change
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Step 4: Contact & Fulfillment --}}
                        <div id="step4Panel" style="display:none">
                            <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
                                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <i class="fa-solid fa-address-card text-[#0078C8]"></i>
                                        <h3 class="font-bold text-slate-800 uppercase tracking-widest text-sm">Step 4:
                                            Fulfillment Details</h3>
                                    </div>
                                </div>
                                <div class="p-8 space-y-12 text-left">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                                        {{-- Pickup Contact --}}
                                        <div class="space-y-8">
                                            <div class="flex items-center gap-4 pb-4 border-b-2 border-slate-100">
                                                <div
                                                    class="w-10 h-10 bg-blue-50 rounded-2xl flex items-center justify-center text-[#0078C8] shadow-sm">
                                                    <i class="fa-solid fa-truck-pickup text-lg"></i>
                                                </div>
                                                <div>
                                                    <h4
                                                        class="font-black text-slate-900 text-[11px] uppercase tracking-[0.2em]">
                                                        Pickup Information</h4>
                                                    <p class="text-[10px] text-slate-400 font-bold mt-0.5"
                                                        id="pickupWindowText">Window: 09:00 - 17:00</p>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 gap-4">
                                                <div class="space-y-1">
                                                    <label
                                                        class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">First
                                                        Name *</label>
                                                    <input type="text" id="pickupFirstName"
                                                        class="w-full py-2.5 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:border-blue-500 focus:bg-white transition-all font-bold text-slate-700 text-sm"
                                                        placeholder="e.g. John">
                                                </div>
                                                <div class="space-y-1">
                                                    <label
                                                        class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Last
                                                        Name *</label>
                                                    <input type="text" id="pickupLastName"
                                                        class="w-full py-2.5 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:border-blue-500 focus:bg-white transition-all font-bold text-slate-700 text-sm"
                                                        placeholder="e.g. Doe">
                                                </div>
                                                <div class="space-y-1">
                                                    <label
                                                        class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Email
                                                        Address *</label>
                                                    <input type="email" id="pickupEmail"
                                                        class="w-full py-2.5 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:border-blue-500 focus:bg-white transition-all font-bold text-slate-700 text-sm"
                                                        placeholder="john@example.com">
                                                </div>
                                                <div class="space-y-1">
                                                    <label
                                                        class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Phone
                                                        Number *</label>
                                                    <input type="text" id="pickupPhone"
                                                        class="w-full py-2.5 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:border-blue-500 focus:bg-white transition-all font-bold text-slate-700 text-sm"
                                                        placeholder="+1 (555) 000-0000">
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Delivery Contact --}}
                                        <div class="space-y-8">
                                            <div class="flex items-center gap-4 pb-4 border-b-2 border-slate-100">
                                                <div
                                                    class="w-10 h-10 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 shadow-sm">
                                                    <i class="fa-solid fa-house-chimney-check text-lg"></i>
                                                </div>
                                                <div>
                                                    <h4
                                                        class="font-black text-slate-900 text-[11px] uppercase tracking-[0.2em]">
                                                        Delivery Information</h4>
                                                    <p class="text-[10px] text-slate-400 font-bold mt-0.5"
                                                        id="deliveryLocationText">Standard Delivery</p>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 gap-4">
                                                <div class="space-y-1">
                                                    <label
                                                        class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">First
                                                        Name *</label>
                                                    <input type="text" id="deliveryFirstName"
                                                        class="w-full py-2.5 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:border-emerald-500 focus:bg-white transition-all font-bold text-slate-700 text-sm"
                                                        placeholder="e.g. Jane">
                                                </div>
                                                <div class="space-y-1">
                                                    <label
                                                        class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Last
                                                        Name *</label>
                                                    <input type="text" id="deliveryLastName"
                                                        class="w-full py-2.5 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:border-emerald-500 focus:bg-white transition-all font-bold text-slate-700 text-sm"
                                                        placeholder="e.g. Smith">
                                                </div>
                                                <div class="space-y-1">
                                                    <label
                                                        class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Email
                                                        Address *</label>
                                                    <input type="email" id="deliveryEmail"
                                                        class="w-full py-2.5 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:border-emerald-500 focus:bg-white transition-all font-bold text-slate-700 text-sm"
                                                        placeholder="jane@example.com">
                                                </div>
                                                <div class="space-y-1">
                                                    <label
                                                        class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Phone
                                                        Number *</label>
                                                    <input type="text" id="deliveryPhone"
                                                        class="w-full py-2.5 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:border-emerald-500 focus:bg-white transition-all font-bold text-slate-700 text-sm"
                                                        placeholder="+1 (555) 000-1111">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer: Navigation Buttons --}}
                        <div class="mt-8 flex items-center justify-between">
                            <button type="button" id="prevBtn" style="display:none"
                                class="px-8 py-4 bg-white border border-slate-200 rounded-2xl text-xs font-black uppercase tracking-[0.2em] text-slate-400 hover:text-slate-900 hover:border-slate-900 transition-all">
                                <i class="fa-solid fa-arrow-left me-2"></i> Previous
                            </button>
                            <div id="step1Placeholder"></div>
                            <div class="flex gap-4">
                                <button type="button" id="saveDraftBtn"
                                    class="px-8 py-4 bg-slate-100 rounded-2xl text-xs font-black uppercase tracking-[0.2em] text-slate-600 hover:bg-slate-200 transition-all disabled:opacity-50">
                                    Save Draft
                                </button>
                                <button type="button" id="nextBtn"
                                    class="px-10 py-4 bg-[#0078C8] text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-xl shadow-blue-500/30 hover:scale-[1.03] active:scale-100 transition-all disabled:opacity-30 disabled:grayscale disabled:cursor-not-allowed">
                                    Continue to Step <span id="nextStepNum">2</span> <i
                                        class="fa-solid fa-arrow-right ms-2"></i>
                                </button>
                                <button type="button" id="submitBtn" style="display:none"
                                    class="px-12 py-4 bg-[#0078C8] text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-xl shadow-blue-500/30 hover:scale-[1.03] active:scale-100 transition-all disabled:opacity-30 disabled:grayscale disabled:cursor-not-allowed">
                                    Complete Booking <i class="fa-solid fa-circle-check ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ─── STATE ────────────────────────────────────────────────────────────────
            const state = {
                step: 1,
                saving: false,
                freightDropdownOpen: false,
                formData: {
                    db_id: {{ isset($booking) ? $booking->id : 'null' }},
                    serviceType: '{{ isset($booking) ? $booking->service_type : 'LTL' }}',
                    originCountry: '{{ isset($booking) ? $booking->origin_country : 'US' }}',
                    destCountry: '{{ isset($booking) ? $booking->dest_country : 'US' }}',
                    originPostal: '{{ isset($booking) ? $booking->origin_postal : '' }}',
                    destPostal: '{{ isset($booking) ? $booking->dest_postal : '' }}',
                    originType: '{{ isset($booking) ? $booking->origin_type : 'Business' }}',
                    destType: '{{ isset($booking) ? $booking->dest_type : 'Business' }}',
                    originCompany: '{{ isset($booking) ? $booking->origin_company : '' }}',
                    originStreet: '{{ isset($booking) ? $booking->origin_street : '' }}',
                    originStreet2: '{{ isset($booking) ? $booking->origin_street2 : '' }}',
                    originCity: '{{ isset($booking) ? $booking->origin_city : '' }}',
                    originState: '{{ isset($booking) ? $booking->origin_state : '' }}',
                    destCompany: '{{ isset($booking) ? $booking->dest_company : '' }}',
                    destStreet: '{{ isset($booking) ? $booking->dest_street : '' }}',
                    destStreet2: '{{ isset($booking) ? $booking->dest_street2 : '' }}',
                    destCity: '{{ isset($booking) ? $booking->dest_city : '' }}',
                    destState: '{{ isset($booking) ? $booking->dest_state : '' }}',
                    services: {!! isset($booking) ? $booking->services : '[]' !!},
                    loadingDate: '{{ isset($booking) && $booking->loading_date ? $booking->loading_date->format('Y-m-d') : '' }}',
                    pickDate: '{{ isset($booking) && $booking->pick_date ? $booking->pick_date->format('Y-m-d') : '' }}',
                    eta: '{{ isset($booking) && $booking->eta ? $booking->eta->format('Y-m-d') : '' }}',
                    items: {!! isset($booking)
                        ? json_encode(
                            $booking->items->map(function ($item) {
                                return [
                                    'desc' => $item->description,
                                    'packageType' => $item->package_type,
                                    'condition' => $item->condition,
                                    'qty' => $item->quantity,
                                    'l' => $item->length,
                                    'w' => $item->width,
                                    'h' => $item->height,
                                    'weight' => $item->weight,
                                ];
                            }),
                        )
                        : '[{"desc":"","packageType":"Pallets","condition":"New","qty":1,"l":48,"w":40,"h":48,"weight":450}]' !!},
                    secondParty: '{{ isset($booking) ? addslashes($booking->second_party) : '' }}',
                    thirdParty: '{{ isset($booking) ? addslashes($booking->third_party) : '' }}',
                    freightClass: '{{ isset($booking) ? $booking->freight_class : '70' }}',
                    carrierId: '{{ isset($booking) ? $booking->carrier_id : '' }}',
                    carrierRate: {{ isset($booking) && $booking->carrier_rate ? (float)$booking->carrier_rate : 0 }},
                    carrierSearch: '{{ isset($booking) && $booking->carrier ? $booking->carrier->legal_carrier_name : '' }}',
                    pickupTime: '{{ isset($booking) ? $booking->pickup_time : '09:00 - 17:00' }}',
                    pickupFirstName: '{{ isset($booking) ? addslashes($booking->pickup_first_name) : '' }}',
                    pickupLastName: '{{ isset($booking) ? addslashes($booking->pickup_last_name) : '' }}',
                    pickupEmail: '{{ isset($booking) ? addslashes($booking->pickup_email) : '' }}',
                    pickupPhone: '{{ isset($booking) ? addslashes($booking->pickup_phone) : '' }}',
                    deliveryLocationName: '{{ isset($booking) ? addslashes($booking->delivery_location_name) : '' }}',
                    deliveryFirstName: '{{ isset($booking) ? addslashes($booking->delivery_first_name) : '' }}',
                    deliveryLastName: '{{ isset($booking) ? addslashes($booking->delivery_last_name) : '' }}',
                    deliveryEmail: '{{ isset($booking) ? addslashes($booking->delivery_email) : '' }}',
                    deliveryPhone: '{{ isset($booking) ? addslashes($booking->delivery_phone) : '' }}',
                },
                carriers: {!! json_encode(
                    isset($carriers)
                        ? $carriers->map(function ($c) {
                            return ['id' => $c->id, 'name' => $c->legal_carrier_name, 'time' => 'Standard Transit', 'price' => 0];
                        })
                        : [],
                ) !!},
            };

            const STEPS = [{
                    label: 'Step 1',
                    title: 'Route & Quote'
                },
                {
                    label: 'Step 2',
                    title: 'Items Detail'
                },
                {
                    label: 'Step 3',
                    title: 'Carrier Match'
                },
                {
                    label: 'Step 4',
                    title: 'Finalization'
                },
            ];

            const PACKAGE_TYPES = [{
                    value: 'Pallet (48"x40")',
                    label: 'Pallet (48"x40")',
                    icon: 'fa-pallet',
                    l: 48,
                    w: 40,
                    h: 48
                },
                {
                    value: 'Pallet (48"x48")',
                    label: 'Pallet (48"x48")',
                    icon: 'fa-pallet',
                    l: 48,
                    w: 48,
                    h: 48
                },
                {
                    value: 'Pallet (Custom Dimensions)',
                    label: 'Pallet (Custom Dimensions)',
                    icon: 'fa-boxes-stacked'
                },
                {
                    value: 'Box',
                    label: 'Box',
                    icon: 'fa-box'
                },
                {
                    value: 'Crate',
                    label: 'Crate',
                    icon: 'fa-box-open'
                },
                {
                    value: 'Bundle',
                    label: 'Bundle',
                    icon: 'fa-box-archive'
                },
                {
                    value: 'Drum',
                    label: 'Drum',
                    icon: 'fa-drum'
                },
                {
                    value: 'Roll',
                    label: 'Roll',
                    icon: 'fa-scroll'
                },
                {
                    value: 'Bale',
                    label: 'Bale',
                    icon: 'fa-boxes-packing'
                },
            ];

            const FREIGHT_CLASSES = [{
                    value: '50',
                    label: 'Class 50',
                    desc: 'Very dense (Steel, Iron, Coal, Cement, Sand, Rock, Ore, Lumber, Raw material, Metal bar, Pipe, Rod)'
                },
                {
                    value: '55',
                    label: 'Class 55',
                    desc: '35-50 lbs/ft³ (Bricks, Copy paper, Hardwood, Machinery, Ceramic)'
                },
                {
                    value: '60',
                    label: 'Class 60',
                    desc: '30-35 lbs/ft³ (Car parts, Auto parts, Ceramic tile, Metal parts, Scrap metal)'
                },
                {
                    value: '65',
                    label: 'Class 65',
                    desc: '22.5-30 lbs/ft³ (Books, Magazines, Bottled water, Beverages, Juice, Dry food, Grain, Flour, Sugar)'
                },
                {
                    value: '70',
                    label: 'Class 70',
                    desc: '15-22.5 lbs/ft³ (Food items, Engine, Motor, Transmission, Plastic, Rubber, Chemicals, Paint, Glass)'
                },
                {
                    value: '77.5',
                    label: 'Class 77.5',
                    desc: '13.5-15 lbs/ft³ (Tires, Snowmobiles, Axle)'
                },
                {
                    value: '85',
                    label: 'Class 85',
                    desc: '12-13.5 lbs/ft³ (Pharmaceuticals, Tools, Hardware, Hand tools, Power tools, Drill, Saw, Hammer, Wrench)'
                },
                {
                    value: '92.5',
                    label: 'Class 92.5',
                    desc: '10.5-12 lbs/ft³ (Computer, Laptop, Desktop, PC, Server, Monitor, Screen, Printer, Scanner, Electronics, Cabinets)'
                },
                {
                    value: '100',
                    label: 'Class 100',
                    desc: '9-10.5 lbs/ft³ (Cereal, Wine, Beer, Spirits, Alcohol, Soda, Boat covers, Groceries, Snacks)'
                },
                {
                    value: '110',
                    label: 'Class 110',
                    desc: '8-9 lbs/ft³ (Metal furniture, Kitchen cabinets, Office furniture, Desk, Shelving)'
                },
                {
                    value: '125',
                    label: 'Class 125',
                    desc: '7-8 lbs/ft³ (Small appliances, Washer, Dryer, Dishwasher, Microwave, Refrigerator, Oven)'
                },
                {
                    value: '150',
                    label: 'Class 150',
                    desc: '6-7 lbs/ft³ (Motorcycles, ATV, Jet ski, Boat, Canoe, Kayak, Projector, Television, TV)'
                },
                {
                    value: '175',
                    label: 'Class 175',
                    desc: '5-6 lbs/ft³ (Clothing, Apparel, Garment, Shirt, Pants, Shoes, Jacket, Uniform, Couch, Chair, Sofa)'
                },
                {
                    value: '200',
                    label: 'Class 200',
                    desc: '4-5 lbs/ft³ (Mattress, Box spring, Bedding, Sleeping bag, Cushion, Carpet, Rug, Flooring)'
                },
                {
                    value: '250',
                    label: 'Class 250',
                    desc: '3-4 lbs/ft³ (Bamboo furniture, Foam, Pillow, Wood shavings, Sawdust, Packing peanuts)'
                },
                {
                    value: '300',
                    label: 'Class 300',
                    desc: '2-3 lbs/ft³ (Model airplane, Kite, Balloon, Feather, Light bulb, Lamp)'
                },
                {
                    value: '400',
                    label: 'Class 400',
                    desc: '1-2 lbs/ft³ (Deer antlers, Ping pong, Table tennis, Surfboard, Paddleboard)'
                },
                {
                    value: '500',
                    label: 'Class 500',
                    desc: 'Less than 1 lb/ft³ (Ping pong balls, Feathers, Gold dust)'
                },
            ];

            // ─── HELPERS ──────────────────────────────────────────────────────────────
            const $ = id => document.getElementById(id);
            const show = el => {
                if (el) el.style.display = '';
            };
            const hide = el => {
                if (el) el.style.display = 'none';
            };

            function esc(str) {
                return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(
                    /"/g, '&quot;');
            }

            function validateCurrentStep() {
                const d = state.formData;
                if (state.step === 1) return d.originPostal && d.destPostal && d.loadingDate && d.originType && d
                    .destType;
                if (state.step === 2) return d.items.length > 0 && d.items.every(i => i.desc && i.qty > 0 && i
                    .weight > 0 && i.l > 0 && i.w > 0 && i.h > 0);
                if (state.step === 3) return d.carrierId && d.carrierRate > 0;
                if (state.step === 4) return d.pickupFirstName && d.pickupLastName && d.pickupEmail && d
                    .pickupPhone &&
                    d.deliveryFirstName && d.deliveryLastName && d.deliveryEmail && d.deliveryPhone;
                return true;
            }

            function totalPallets() {
                return state.formData.items.reduce((sum, i) => sum + (parseInt(i.qty) || 0), 0);
            }

            function calculateClass() {
                let totalWeight = 0,
                    totalCubicFeet = 0;
                state.formData.items.forEach(item => {
                    totalWeight += (parseInt(item.qty) || 0) * (parseFloat(item.weight) || 0);
                    totalCubicFeet += (parseInt(item.qty) || 0) * (parseFloat(item.l) || 0) * (parseFloat(
                        item.w) || 0) * (parseFloat(item.h) || 0) / 1728;
                });
                if (totalWeight > 0 && totalCubicFeet > 0) {
                    const d = totalWeight / totalCubicFeet;
                    state.formData.freightClass =
                        d < 1 ? '400' : d < 2 ? '300' : d < 4 ? '250' : d < 6 ? '175' :
                        d < 8 ? '125' : d < 10 ? '100' : d < 12 ? '92.5' : d < 15 ? '85' :
                        d < 22.5 ? '70' : d < 30 ? '65' : '50';
                }
            }

            // ─── FREIGHT CLASS BY DESCRIPTION KEYWORDS ────────────────────────────────
            const FREIGHT_CLASS_KEYWORDS = [
                { keywords: ['car','automobile','vehicle','truck','motorcycle','suv','van'],                                                         cls: '400' },
                { keywords: ['boat','canoe','kayak','surfboard','paddleboard'],                                                                      cls: '400' },
                { keywords: ['ping pong','table tennis','balloon','feather','kite'],                                                                 cls: '400' },
                { keywords: ['mattress','bedding','pillow','cushion','foam','sleeping bag'],                                                         cls: '300' },
                { keywords: ['wood shavings','sawdust','peanuts','packing peanut'],                                                                  cls: '250' },
                { keywords: ['cabinet','sofa','couch','furniture','chair','desk','bookshelf','wardrobe','dresser','armchair'],                       cls: '200' },
                { keywords: ['clothing','apparel','garment','shirt','pants','dress','shoes','boots','jacket','coat','uniform'],                      cls: '175' },
                { keywords: ['computer','laptop','desktop','pc','server','monitor','printer','scanner','electronics'], cls: '92.5' },
                { keywords: ['tv','television','projector','appliance','refrigerator','washer','dryer','motorcycle','atv','jet ski'], cls: '150' },
                { keywords: ['carpet','rug','tile','flooring','laminate'],                                                                           cls: '125' },
                { keywords: ['book','magazine','newspaper','paper','documents','files','binders'],                                                    cls: '100' },
                { keywords: ['wine','beer','spirits','alcohol','beverage','drink','juice','water','soda'],                                           cls: '100' },
                { keywords: ['food','groceries','snack','candy','chocolate','fruit','vegetable','grain','flour','sugar'],                            cls: '92.5' },
                { keywords: ['tools','hand tool','power tool','drill','saw','hammer','wrench','screwdriver'],                                        cls: '85' },
                { keywords: ['engine','motor','transmission','axle','auto parts','spare parts','machinery','equipment'],                             cls: '85' },
                { keywords: ['steel','metal','iron','aluminum','copper','brass','pipe','tube','rod','bar','wire'],                                   cls: '70' },
                { keywords: ['concrete','cement','brick','block','stone','gravel','sand','aggregate'],                                               cls: '70' },
                { keywords: ['pallet','skid','drum','barrel','container','crate','box','carton'],                                                    cls: '70' },
                { keywords: ['chemical','solvent','paint','coating','adhesive','resin'],                                                             cls: '70' },
                { keywords: ['glass','mirror','window','windshield'],                                                                                cls: '85' },
                { keywords: ['plastic','polymer','rubber','hose','gasket','seal'],                                                                   cls: '70' },
                { keywords: ['scrap','recycled','waste','debris'],                                                                                   cls: '65' },
                { keywords: ['coal','ore','rock','mineral','raw material'],                                                                          cls: '50' },
            ];

            function suggestFreightClassFromDesc(desc) {
                if (!desc || desc.trim().length < 3) return null;
                const lower = desc.toLowerCase();

                // Primary: match against FREIGHT_CLASSES descriptions (single source of truth)
                for (const fc of FREIGHT_CLASSES) {
                    const match = fc.desc.match(/\(([^)]+)\)/);
                    if (match) {
                        const examples = match[1].split(',').map(s => s.trim().toLowerCase());
                        if (examples.some(kw => kw.length >= 3 && lower.includes(kw))) {
                            return fc.value;
                        }
                    }
                }

                // Fallback: explicit keyword table for broader synonyms
                for (const entry of FREIGHT_CLASS_KEYWORDS) {
                    if (entry.keywords.some(kw => lower.includes(kw))) {
                        return entry.cls;
                    }
                }

                return null;
            }

            function filteredCarriers() {
                const q = (state.formData.carrierSearch || '').toLowerCase();
                return q ? state.carriers.filter(c => c.name.toLowerCase().includes(q)) : state.carriers;
            }

            // ─── RENDER ───────────────────────────────────────────────────────────────
            function renderStepsNav() {
                const nav = $('stepsNav');
                nav.innerHTML = STEPS.map((s, i) => {
                    const n = i + 1;
                    const isActive = state.step === n;
                    const isDone = state.step > n;
                    const isLocked = n > state.step && !validateCurrentStep();
                    return `<button type="button" data-step="${n}"
                class="step-nav-btn w-full flex items-center gap-4 p-4 rounded-xl transition-all duration-300 group
                ${isActive ? 'bg-blue-50 border-l-4 border-[#0078C8]' : isLocked ? 'opacity-40 cursor-not-allowed grayscale border-l-4 border-transparent' : 'hover:bg-slate-50 border-l-4 border-transparent'}">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-all
                    ${state.step >= n ? 'bg-[#0078C8] text-white shadow-md shadow-blue-100' : 'bg-slate-100 text-slate-400'}">
                    ${isDone ? '<i class="fa-solid fa-check text-[10px]"></i>' : `<span class="text-xs font-black">${n}</span>`}
                </div>
                <div class="text-left">
                    <p class="text-[10px] font-black uppercase tracking-widest ${isActive ? 'text-[#0078C8]' : 'text-slate-400'}">${s.label}</p>
                    <p class="text-xs font-bold text-slate-900 mt-0.5">${s.title}</p>
                </div>
                ${isDone ? '<div class="ml-auto"><i class="fa-solid fa-circle-check text-[#0078C8] text-sm"></i></div>' : ''}
            </button>`;
                }).join('');

                nav.querySelectorAll('.step-nav-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const n = parseInt(this.dataset.step);
                        if (n < state.step) goToStep(n);
                        else if (n === state.step + 1 && validateCurrentStep()) goToStep(n);
                    });
                });
            }

            function renderServiceTypes() {
                const types = [{
                        type: 'LTL',
                        icon: 'fa-boxes-stacked',
                        sub: 'Less than truckload'
                    },
                    {
                        type: 'Full Truckload',
                        icon: 'fa-truck-moving',
                        sub: 'Dedicated Unit'
                    },
                    {
                        type: 'Small Parcel',
                        icon: 'fa-box-open',
                        sub: 'Individual items'
                    },
                ];
                const grid = $('serviceTypeGrid');
                grid.innerHTML = types.map(({
                    type,
                    icon,
                    sub
                }) => {
                    const active = state.formData.serviceType === type;
                    return `<button type="button" data-service="${esc(type)}"
                class="service-type-btn relative p-5 rounded-2xl border-2 transition-all duration-300 text-left group
                ${active ? 'border-[#0078C8] bg-blue-50/50 shadow-md' : 'border-slate-100 hover:border-slate-300 bg-white'}">
                <div class="flex justify-between items-start mb-2">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors
                        ${active ? 'bg-[#0078C8] text-white' : 'bg-slate-50 text-slate-400 group-hover:bg-slate-100'}">
                        <i class="fa-solid ${icon}"></i>
                    </div>
                    ${active ? '<div class="text-[#0078C8]"><i class="fa-solid fa-circle-check"></i></div>' : ''}
                </div>
                <h4 class="font-bold text-slate-900">${esc(type)}</h4>
                <p class="text-[10px] text-slate-500 mt-1 uppercase tracking-tight">${esc(sub)}</p>
            </button>`;
                }).join('');

                grid.querySelectorAll('.service-type-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        state.formData.serviceType = this.dataset.service;
                        renderServiceTypes();
                        updateSummary();
                    });
                });
            }

            function renderOriginTypeButtons() {
                const container = $('originTypeButtons');
                container.innerHTML = ['Business', 'Residential', 'Carrier Terminal'].map(loc => `
            <button type="button" data-origin-type="${esc(loc)}"
                class="origin-type-btn px-4 py-2 rounded-lg text-xs font-bold transition-all
                ${state.formData.originType === loc ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">
                ${esc(loc)}
            </button>`).join('');
                container.querySelectorAll('.origin-type-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        state.formData.originType = this.dataset.originType;
                        renderOriginTypeButtons();
                        updateNavButtons();
                    });
                });
            }

            function renderItems() {
                const container = $('itemsContainer');
                container.innerHTML = state.formData.items.map((item, index) => {
                    const pkgType = PACKAGE_TYPES.find(t => t.value === item.packageType);
                    const pkgIcon = pkgType ? pkgType.icon : 'fa-box';
                    const isCustom = item.packageType === 'Pallet (Custom Dimensions)';
                    const pkgLabel = isCustom ? 'Pallet' : esc(item.packageType);
                    return `<div class="px-8 py-10 rounded-[2.5rem] border border-slate-100 bg-white shadow-sm relative group/item" data-item-index="${index}">
                <div class="absolute -right-2 -top-2 opacity-0 group-hover/item:opacity-100 transition-all">
                    ${state.formData.items.length > 1 ? `<button type="button" data-remove="${index}"
                            class="remove-item-btn w-8 h-8 rounded-full bg-white shadow-lg border border-red-50 text-red-400 hover:bg-red-500 hover:text-white flex items-center justify-center transition-all">
                            <i class="fa-solid fa-trash-can text-xs"></i></button>` : ''}
                </div>
                <div class="space-y-6">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-800">Item description *</label>
                        <input type="text" data-field="desc" data-index="${index}" class="item-input w-full py-3 px-4 text-sm rounded-xl border-slate-200 focus:ring-2 focus:ring-[#0078C8] focus:border-[#0078C8] transition-all"
                            value="${esc(item.desc)}" placeholder="e.g. electronics, steel coil, mattress...">
                        <div id="fc-suggest-${index}" class="mt-1 hidden">
                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-[#0078C8] bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-full">
                                <i class="fa-solid fa-wand-magic-sparkles text-[8px]"></i>
                                <span class="fc-suggest-text"></span>
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-800">Packaging type *</label>
                            <div class="relative">
                                <button type="button" data-pkg-toggle="${index}"
                                    class="pkg-toggle-btn w-full py-3 px-4 text-sm rounded-xl border-2 border-slate-100 bg-white flex items-center justify-between hover:border-slate-300 transition-all text-left shadow-sm">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid text-slate-400 ${pkgIcon}"></i>
                                        <span class="text-slate-700">${esc(item.packageType)}</span>
                                    </div>
                                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200 pkg-chevron"></i>
                                </button>
                                <div id="pkgDropdown-${index}" class="absolute z-50 w-full mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden py-2 max-h-60 overflow-y-auto" style="display:none">
                                    ${PACKAGE_TYPES.map(type => `
                                            <button type="button" data-pkg-select="${index}" data-pkg-value="${esc(type.value)}"
                                                class="pkg-select-btn w-full px-4 py-2.5 text-sm text-left hover:bg-slate-50 flex items-center gap-3 transition-colors
                                                ${item.packageType === type.value ? 'bg-blue-50 text-[#0078C8]' : 'text-slate-600'}">
                                                <div class="w-8 h-8 rounded-lg flex items-center justify-center ${item.packageType === type.value ? 'bg-blue-100' : 'bg-slate-100/50'}">
                                                    <i class="fa-solid ${type.icon}"></i>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="font-bold">${esc(type.label)}</span>
                                                    ${type.l ? `<span class="text-[9px] text-slate-400 font-medium">${type.l} x ${type.w} x ${type.h} in</span>` : ''}
                                                </div>
                                                ${item.packageType === type.value ? '<i class="fa-solid fa-check ml-auto text-[10px]"></i>' : ''}
                                            </button>`).join('')}
                                </div>
                            </div>
                            <a href="#" class="text-[10px] text-blue-500 hover:underline font-bold flex items-center gap-2 mt-2">
                                <i class="fa-solid fa-circle-info"></i> Search for packaging help with Taskrabbit
                            </a>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-800">Dimensions *</label>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">L × W × H (in)</span>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                ${['l','w','h'].map(f => `
                                    <div class="relative">
                                        <input type="number" data-field="${f}" data-index="${index}" class="item-input w-full py-3 px-4 text-sm rounded-xl border-2 border-slate-100 focus:border-[#0078C8] focus:ring-0 transition-all" value="${item[f] || ''}" placeholder="0">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-300">in</span>
                                    </div>`).join('')}
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-800">Weight of 1 ${pkgLabel} *</label>
                                <div class="relative">
                                    <input type="number" data-field="weight" data-index="${index}" class="item-input w-full py-3 px-4 text-sm rounded-xl border-2 border-slate-100 focus:border-[#0078C8] focus:ring-0 font-black transition-all" value="${item.weight || ''}" placeholder="0">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-300">pounds</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-800">Number of ${pkgLabel}s *</label>
                                <input type="number" data-field="qty" data-index="${index}" class="item-input w-full py-3 px-4 text-sm rounded-xl border-2 border-slate-100 focus:border-[#0078C8] focus:ring-0 font-black transition-all" value="${item.qty || ''}" placeholder="1">
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row items-end justify-between pt-4 border-t border-slate-50 gap-6">
                        <div class="w-full md:w-auto space-y-3">
                            <label class="text-xs font-bold text-slate-800">What is the condition of this item? *</label>
                            <div class="flex gap-4">
                                ${['New','Used'].map(cond => `
                                    <button type="button" data-condition="${cond}" data-index="${index}" class="condition-btn flex items-center gap-3 transition-all">
                                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all ${item.condition === cond ? 'border-[#0078C8]' : 'border-slate-200'}">
                                            ${item.condition === cond ? '<div class="w-2.5 h-2.5 rounded-full bg-[#0078C8]"></div>' : ''}
                                        </div>
                                        <span class="text-sm font-medium text-slate-600">${cond}</span>
                                    </button>`).join('')}
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Total shipment weight</p>
                            <p class="text-lg font-black text-slate-900">${((item.qty||0) * (item.weight||0))} pounds</p>
                        </div>
                    </div>
                </div>
            </div>`;
                }).join('');

                // Attach item listeners
                container.querySelectorAll('.item-input').forEach(input => {
                    input.addEventListener('input', function() {
                        const idx = parseInt(this.dataset.index);
                        const field = this.dataset.field;
                        const numFields = ['qty', 'l', 'w', 'h', 'weight'];
                        state.formData.items[idx][field] = numFields.includes(field) ? (parseFloat(
                            this.value) || 0) : this.value;
                        if (numFields.includes(field)) {
                            calculateClass();
                            updateFreightClassDisplay();
                            updateSummary();
                        }
                        if (field === 'desc') {
                            const suggested = suggestFreightClassFromDesc(this.value);
                            const badge = document.getElementById(`fc-suggest-${idx}`);
                            if (suggested) {
                                state.formData.freightClass = suggested;
                                updateFreightClassDisplay();
                                renderFreightClassList();
                                updateSummary();
                                if (badge) {
                                    badge.classList.remove('hidden');
                                    badge.querySelector('.fc-suggest-text').textContent =
                                        `Freight Class ${suggested} auto-selected`;
                                }
                            } else {
                                if (badge) badge.classList.add('hidden');
                            }
                        }
                        updateNavButtons();
                    });
                });

                container.querySelectorAll('.remove-item-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        state.formData.items.splice(parseInt(this.dataset.remove), 1);
                        calculateClass();
                        renderItems();
                        updateFreightClassDisplay();
                        updateSummary();
                    });
                });

                container.querySelectorAll('.condition-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        state.formData.items[parseInt(this.dataset.index)].condition = this.dataset
                            .condition;
                        renderItems();
                    });
                });

                container.querySelectorAll('[data-pkg-toggle]').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const idx = this.dataset.pkgToggle;
                        const dropdown = $(`pkgDropdown-${idx}`);
                        const isOpen = dropdown.style.display !== 'none';
                        document.querySelectorAll('[id^="pkgDropdown-"]').forEach(d => d.style
                            .display = 'none');
                        if (!isOpen) show(dropdown);
                    });
                });

                container.querySelectorAll('.pkg-select-btn').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const idx = parseInt(this.dataset.pkgSelect);
                        const value = this.dataset.pkgValue;
                        const type = PACKAGE_TYPES.find(t => t.value === value);
                        state.formData.items[idx].packageType = value;
                        if (type && type.l) {
                            state.formData.items[idx].l = type.l;
                            state.formData.items[idx].w = type.w;
                            state.formData.items[idx].h = type.h;
                        }
                        calculateClass();
                        renderItems();
                        updateFreightClassDisplay();
                        updateSummary();
                    });
                });

            }

            function renderFreightClassList() {
                const list = $('freightClassList');
                list.innerHTML = FREIGHT_CLASSES.map(fc => `
            <button type="button" data-fc="${fc.value}"
                class="fc-btn w-full px-4 py-3 text-left hover:bg-slate-50 flex flex-col transition-colors border-b border-slate-50 last:border-0
                ${state.formData.freightClass === fc.value ? 'bg-blue-50' : ''}">
                <div class="flex items-center justify-between w-full">
                    <span class="text-xs font-black ${state.formData.freightClass === fc.value ? 'text-[#0078C8]' : 'text-slate-900'}">${fc.label}</span>
                    ${state.formData.freightClass === fc.value ? '<i class="fa-solid fa-check text-[10px] text-[#0078C8]"></i>' : ''}
                </div>
                <span class="text-[10px] text-slate-400 mt-1">${fc.desc}</span>
            </button>`).join('');

                list.querySelectorAll('.fc-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        state.formData.freightClass = this.dataset.fc;
                        closeFreightDropdown();
                        updateFreightClassDisplay();
                        renderFreightClassList();
                        updateSummary();
                    });
                });
            }

            function renderCarrierList() {
                const list = $('carrierList');
                const filtered = filteredCarriers();
                if (filtered.length === 0) {
                    list.innerHTML = `<div class="px-4 py-8 text-center">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-truck-ramp-box text-2xl text-slate-200"></i>
                </div>
                <p class="text-sm font-black text-slate-900">Carrier Not Found</p>
                <p class="text-[10px] text-slate-400 mt-1 mb-6">Press the button below to add this carrier</p>
                <button type="button" id="createCarrierFromSearch"
                    class="w-full py-4 bg-[#0078C8] text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-blue-600 transition-all shadow-lg shadow-blue-100 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i> Create New Carrier
                </button></div>`;
                    const btn = $('createCarrierFromSearch');
                    if (btn) btn.addEventListener('click', () => {
                        const nameInput = $('nc_legal_carrier_name');
                        if (nameInput) nameInput.value = state.formData.carrierSearch;
                        hide($('carrierDropdown'));
                        show($('carrierModal'));
                    });
                } else {
                    list.innerHTML = filtered.map(c => `
                <button type="button" data-carrier-id="${c.id}" data-carrier-name="${esc(c.name)}"
                    class="carrier-select-btn w-full px-4 py-3.5 flex items-center justify-between hover:bg-[#0078C8] hover:text-white rounded-2xl transition-all group/item mb-1 last:mb-0">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-[#0078C8] group-hover/item:bg-white/20 group-hover/item:text-white transition-colors">
                            <i class="fa-solid fa-truck"></i>
                        </div>
                        <div class="text-left">
                            <p class="text-[13px] font-black uppercase tracking-tight">${esc(c.name)}</p>
                            <p class="text-[10px] text-slate-400 group-hover/item:text-white/70">${esc(c.time)}</p>
                        </div>
                    </div>
                    ${String(state.formData.carrierId) === String(c.id) ? '<div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center"><i class="fa-solid fa-check text-white text-[10px]"></i></div>' : ''}
                </button>`).join('');

                    list.querySelectorAll('.carrier-select-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            state.formData.carrierId = this.dataset.carrierId;
                            state.formData.carrierSearch = this.dataset.carrierName;
                            $('carrierSearch').value = this.dataset.carrierName;
                            hide($('carrierDropdown'));
                            updateSelectedCarrier();
                            updateSummary();
                            updateNavButtons();
                        });
                    });
                }
            }

            // ─── UPDATE DOM ───────────────────────────────────────────────────────────
            function updateStepPanels() {
                [1, 2, 3, 4].forEach(n => {
                    const panel = $(`step${n}Panel`);
                    if (panel) panel.style.display = state.step === n ? '' : 'none';
                });
            }

            function updateNavButtons() {
                const prevBtn = $('prevBtn');
                const nextBtn = $('nextBtn');
                const submitBtn = $('submitBtn');
                const placeholder = $('step1Placeholder');
                const saveDraftBtn = $('saveDraftBtn');
                const valid = validateCurrentStep();

                prevBtn.style.display = state.step > 1 ? '' : 'none';
                placeholder.style.display = state.step === 1 ? '' : 'none';
                nextBtn.style.display = state.step < 4 ? '' : 'none';
                submitBtn.style.display = state.step === 4 ? '' : 'none';

                nextBtn.disabled = state.saving || !valid;
                submitBtn.disabled = state.saving || !valid;
                saveDraftBtn.disabled = state.saving;
                $('nextStepNum').textContent = state.step + 1;
            }

            function updateProgressBar() {
                $('progressBar').style.width = `${(state.step / 4) * 100}%`;
            }

            function updateFreightClassDisplay() {
                const fc = state.formData.freightClass || '---';
                $('freightClassDisplay').textContent = fc;
                $('freightClassBtnText').textContent = `Class ${fc}`;
            }

            function updateSelectedCarrier() {
                const card = $('selectedCarrierCard');
                if (state.formData.carrierId) {
                    const carrier = state.carriers.find(c => String(c.id) === String(state.formData.carrierId));
                    if (carrier) $('selectedCarrierName').textContent = carrier.name;
                    show(card);
                } else {
                    hide(card);
                }
            }

            function updateSummary() {
                $('summaryService').textContent = state.formData.serviceType || '-';
                $('summaryItems').textContent = totalPallets();
                $('summaryClass').textContent = state.formData.freightClass || '-';
                const rate = parseFloat(state.formData.carrierRate) || 0;
                if (rate > 0) {
                    $('summaryRate').textContent = '$' + rate;
                    show($('summaryRateRow'));
                } else {
                    hide($('summaryRateRow'));
                }
            }

            function closeFreightDropdown() {
                hide($('freightClassDropdown'));
                state.freightDropdownOpen = false;
                $('freightClassChevron').classList.remove('rotate-180');
            }

            // ─── NAVIGATION ───────────────────────────────────────────────────────────
            function goToStep(n) {
                state.step = n;
                updateStepPanels();
                updateNavButtons();
                updateProgressBar();
                renderStepsNav();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            // ─── FORM SYNC ────────────────────────────────────────────────────────────
            function syncFromDOM() {
                ['secondParty', 'thirdParty',
                    'originCountry', 'originPostal', 'originCompany', 'originStreet', 'originStreet2', 'originCity', 'originState',
                    'destCountry', 'destPostal', 'destCompany', 'destStreet', 'destStreet2', 'destCity', 'destState',
                    'destType', 'loadingDate', 'pickDate', 'eta', 'pickupFirstName', 'pickupLastName', 'pickupEmail', 'pickupPhone',
                    'deliveryFirstName', 'deliveryLastName', 'deliveryEmail', 'deliveryPhone'
                ].forEach(id => {
                    const el = $(id);
                    if (el) state.formData[id] = el.value;
                });
                state.formData.carrierRate = parseFloat($('carrierRate').value) || 0;
                state.formData.services = [];
                [
                    ['service_liftgate', 'Lift Gate'],
                    ['service_inside', 'Inside Access'],
                    ['service_appt', 'Appointment Fee']
                ].forEach(([id, val]) => {
                    if ($(id) && $(id).checked) state.formData.services.push(val);
                });
            }

            // ─── SUBMIT ───────────────────────────────────────────────────────────────
            async function submitForm(isDraft = false) {
                if (state.saving) return;
                syncFromDOM();
                state.saving = true;
                state.formData.is_draft = isDraft;
                updateNavButtons();

                const formData = new FormData();
                Object.keys(state.formData).forEach(key => {
                    if (key === 'items' || key === 'carrierSearch' || key === 'is_draft') return;
                    if (Array.isArray(state.formData[key])) {
                        state.formData[key].forEach(v => formData.append(key + '[]', v));
                    } else {
                        formData.append(key, state.formData[key] ?? '');
                    }
                });
                formData.append('is_draft', isDraft ? '1' : '0');
                state.formData.items.forEach((item, index) => {
                    Object.keys(item).forEach(k => {
                        formData.append(`items[${index}][${k}]`, item[k] ?? '');
                    });
                });

                try {
                    const response = await fetch('{{ route('logistics.manage-booking.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.location.href = '{{ route('logistics.manage-booking.index.inbound') }}';
                    } else {
                        console.error('Server error:', result);
                        alert('Save failed: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Submission error:', error);
                    alert('Request failed: ' + error.message);
                } finally {
                    state.saving = false;
                    updateNavButtons();
                }
            }

            // ─── INIT ─────────────────────────────────────────────────────────────────
            function init() {
                // Pre-fill DOM inputs from state
                const presets = {
                    originCountry:     state.formData.originCountry,
                    originPostal:      state.formData.originPostal,
                    originCompany:     state.formData.originCompany,
                    originStreet:      state.formData.originStreet,
                    originStreet2:     state.formData.originStreet2,
                    originCity:        state.formData.originCity,
                    originState:       state.formData.originState,
                    destCountry:       state.formData.destCountry,
                    destPostal:        state.formData.destPostal,
                    destCompany:       state.formData.destCompany,
                    destStreet:        state.formData.destStreet,
                    destStreet2:       state.formData.destStreet2,
                    destCity:          state.formData.destCity,
                    destState:         state.formData.destState,
                    destType:          state.formData.destType,
                    loadingDate:       state.formData.loadingDate,
                    pickDate:          state.formData.pickDate,
                    eta:               state.formData.eta,
                    carrierSearch:     state.formData.carrierSearch,
                    carrierRate:       state.formData.carrierRate || '',
                    secondParty:       state.formData.secondParty,
                    thirdParty:        state.formData.thirdParty,
                    pickupFirstName:   state.formData.pickupFirstName,
                    pickupLastName:    state.formData.pickupLastName,
                    pickupEmail:       state.formData.pickupEmail,
                    pickupPhone:       state.formData.pickupPhone,
                    deliveryFirstName: state.formData.deliveryFirstName,
                    deliveryLastName:  state.formData.deliveryLastName,
                    deliveryEmail:     state.formData.deliveryEmail,
                    deliveryPhone:     state.formData.deliveryPhone,
                };
                Object.entries(presets).forEach(([id, val]) => {
                    const el = $(id);
                    if (el) el.value = val || '';
                });

                // Pre-check services
                if (Array.isArray(state.formData.services)) {
                    if (state.formData.services.includes('Lift Gate')) $('service_liftgate').checked = true;
                    if (state.formData.services.includes('Inside Access')) $('service_inside').checked = true;
                    if (state.formData.services.includes('Appointment Fee')) $('service_appt').checked = true;
                }

                // Initial renders
                renderStepsNav();
                renderServiceTypes();
                renderOriginTypeButtons();
                renderItems();
                renderFreightClassList();
                updateStepPanels();
                updateNavButtons();
                updateProgressBar();
                updateFreightClassDisplay();
                updateSelectedCarrier();
                updateSummary();
                $('pickupWindowText').textContent = `Window: ${state.formData.pickupTime}`;
                $('deliveryLocationText').textContent = state.formData.deliveryLocationName || 'Standard Delivery';

                // Navigation buttons
                $('nextBtn').addEventListener('click', () => {
                    syncFromDOM();
                    if (validateCurrentStep()) goToStep(state.step + 1);
                });
                $('prevBtn').addEventListener('click', () => goToStep(state.step - 1));
                $('submitBtn').addEventListener('click', () => {
                    syncFromDOM();
                    submitForm(false);
                });
                $('saveDraftBtn').addEventListener('click', () => {
                    syncFromDOM();
                    submitForm(true);
                });

                // Add item
                $('addItemBtn').addEventListener('click', () => {
                    state.formData.items.push({
                        desc: '',
                        packageType: 'Pallet (48"x40")',
                        condition: 'New',
                        qty: 1,
                        l: 48,
                        w: 40,
                        h: 48,
                        weight: 450
                    });
                    renderItems();
                    updateSummary();
                });

                // Freight class dropdown
                $('freightClassBtn').addEventListener('click', function(e) {
                    e.stopPropagation();
                    const dropdown = $('freightClassDropdown');
                    const isOpen = dropdown.style.display !== 'none';
                    if (isOpen) {
                        closeFreightDropdown();
                    } else {
                        show(dropdown);
                        state.freightDropdownOpen = true;
                        $('freightClassChevron').classList.add('rotate-180');
                    }
                });

                // Carrier search
                $('carrierSearch').addEventListener('focus', () => {
                    show($('carrierDropdown'));
                    renderCarrierList();
                });
                $('carrierSearch').addEventListener('input', function() {
                    state.formData.carrierSearch = this.value;
                    renderCarrierList();
                });

                // Carrier rate
                $('carrierRate').addEventListener('input', function() {
                    state.formData.carrierRate = parseFloat(this.value) || 0;
                    updateSummary();
                    updateNavButtons();
                });

                // Clear carrier
                $('clearCarrierBtn').addEventListener('click', () => {
                    state.formData.carrierId = null;
                    state.formData.carrierSearch = '';
                    $('carrierSearch').value = '';
                    updateSelectedCarrier();
                    updateSummary();
                    updateNavButtons();
                });

                // Carrier modal — close
                $('closeCarrierModal').addEventListener('click', () => {
                    hide($('carrierModal'));
                    $('carrierModalError').classList.add('hidden');
                });

                // Carrier modal — create & select (saves to DB via carriers.store)
                $('createCarrierBtn').addEventListener('click', async () => {
                    const btn = $('createCarrierBtn');
                    const icon = $('createCarrierIcon');
                    const label = $('createCarrierLabel');
                    const errorBox = $('carrierModalError');
                    const name = $('nc_legal_carrier_name').value.trim();
                    const mode = $('nc_logistics_mode').value;

                    // Client-side validation
                    errorBox.classList.add('hidden');
                    if (!name) {
                        errorBox.textContent = 'Legal carrier name is required.';
                        errorBox.classList.remove('hidden');
                        return;
                    }
                    if (!mode) {
                        errorBox.textContent = 'Please select a logistics mode.';
                        errorBox.classList.remove('hidden');
                        return;
                    }

                    // Loading state
                    btn.disabled = true;
                    icon.className = 'fa-solid fa-spinner fa-spin';
                    label.textContent = 'Saving…';

                    try {
                        const formData = new FormData();
                        formData.append('legal_carrier_name', name);
                        formData.append('logistics_mode', mode);
                        formData.append('scac_iata_code', $('nc_scac_iata_code').value.trim());
                        formData.append('dot_number', $('nc_dot_number').value.trim());
                        formData.append('mc_authority', $('nc_mc_authority').value.trim());
                        formData.append('safety_rating', $('nc_safety_rating').value);
                        const insuranceFile = $('nc_liability_insurance').files[0];
                        if (insuranceFile) formData.append('liability_insurance', insuranceFile);

                        const response = await fetch('{{ route('logistics.carriers.store') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: formData
                        });
                        const result = await response.json();

                        if (result.success && result.carrier) {
                            // Add to local list and auto-select
                            state.carriers.push({
                                id: result.carrier.id,
                                name: result.carrier.name,
                                time: result.carrier.time,
                                price: 0
                            });
                            state.formData.carrierId = result.carrier.id;
                            state.formData.carrierSearch = result.carrier.name;
                            $('carrierSearch').value = result.carrier.name;

                            // Reset modal fields
                            ['nc_legal_carrier_name', 'nc_scac_iata_code', 'nc_dot_number',
                                'nc_mc_authority'
                            ].forEach(id => $(id).value = '');
                            $('nc_logistics_mode').value = '';
                            $('nc_safety_rating').value = '';
                            $('nc_liability_insurance').value = '';

                            hide($('carrierModal'));
                            updateSelectedCarrier();
                            updateSummary();
                            updateNavButtons();
                        } else {
                            const msg = Array.isArray(result.message) ? result.message.join(' ') : (
                                result.message || 'Failed to create carrier.');
                            errorBox.textContent = msg;
                            errorBox.classList.remove('hidden');
                        }
                    } catch (err) {
                        console.error(err);
                        errorBox.textContent = 'A network error occurred. Please try again.';
                        errorBox.classList.remove('hidden');
                    } finally {
                        btn.disabled = false;
                        icon.className = 'fa-solid fa-plus-circle';
                        label.textContent = 'Create & Select';
                    }
                });

                // Postal code → dropdown autocomplete (local zip_codes table, no external API)
                const ZIP_LOOKUP_URL = '{{ route('logistics.manage-booking.zip-lookup') }}';

                function closeAllPostalDropdowns() {
                    ['originPostalDropdown', 'destPostalDropdown'].forEach(id => {
                        const el = $(id);
                        if (el) el.classList.add('hidden');
                    });
                }
                document.addEventListener('click', closeAllPostalDropdowns);

                async function lookupPostal(prefix) {
                    const postalEl   = $(prefix + 'Postal');
                    const cityEl     = $(prefix + 'City');
                    const stateEl    = $(prefix + 'State');
                    const countryEl  = $(prefix + 'Country');
                    const dropdownEl = $(prefix + 'PostalDropdown');
                    const loadingEl  = $(prefix + 'PostalLoading');
                    if (!postalEl || !cityEl || !stateEl || !dropdownEl) return;

                    const q       = postalEl.value.trim();
                    const country = (countryEl ? countryEl.value : 'US').toUpperCase();

                    if (q.length < 2) {
                        dropdownEl.classList.add('hidden');
                        if (loadingEl) loadingEl.classList.add('hidden');
                        return;
                    }

                    // Show spinner
                    if (loadingEl) loadingEl.classList.remove('hidden');
                    dropdownEl.classList.add('hidden');

                    try {
                        const res    = await fetch(`${ZIP_LOOKUP_URL}?q=${encodeURIComponent(q)}&country=${country}`);
                        const places = await res.json();

                        if (loadingEl) loadingEl.classList.add('hidden');

                        if (!places.length) {
                            dropdownEl.innerHTML = `
                                <div class="px-4 py-3 text-xs text-slate-400 flex items-center gap-2">
                                    <i class="fa-solid fa-triangle-exclamation text-amber-400"></i>
                                    No results for <span class="font-bold text-slate-600 ml-1">"${q}"</span>
                                </div>`;
                            dropdownEl.classList.remove('hidden');
                            return;
                        }

                        dropdownEl.innerHTML = '';

                        // Header
                        const header = document.createElement('div');
                        header.className = 'px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-slate-50 border-b border-slate-100 flex items-center gap-1.5';
                        header.innerHTML = `<i class="fa-solid fa-location-dot text-[#0078C8]"></i>&nbsp;${places.length} result${places.length > 1 ? 's' : ''} &nbsp;·&nbsp; <span class="font-mono text-[#0078C8] ml-1">${q}</span>`;
                        dropdownEl.appendChild(header);

                        places.forEach(place => {
                            const city  = place.city    || '';
                            const state = place.state   || '';
                            const code  = place.zipcode || q;

                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'w-full text-left px-3 py-2.5 hover:bg-blue-50 flex items-center gap-2 border-b border-slate-50 last:border-0 transition-colors group';
                            item.innerHTML = `
                                <i class="fa-solid fa-location-dot text-slate-200 group-hover:text-[#0078C8] text-[10px] flex-shrink-0"></i>
                                <div class="flex-1 min-w-0">
                                    <span class="font-bold text-slate-800 text-xs group-hover:text-[#0078C8]">${city}</span>
                                    <span class="text-slate-400 text-xs ml-1">· ${state}</span>
                                </div>
                                <span class="text-[10px] font-mono font-bold text-[#0078C8] bg-blue-50 group-hover:bg-blue-100 px-2 py-0.5 rounded-lg flex-shrink-0">${code}</span>
                            `;
                            item.addEventListener('mousedown', (e) => {
                                e.preventDefault();
                                cityEl.value   = city;
                                stateEl.value  = state;
                                postalEl.value = code;
                                state.formData[prefix + 'City']   = city;
                                state.formData[prefix + 'State']  = state;
                                state.formData[prefix + 'Postal'] = code;
                                dropdownEl.classList.add('hidden');
                                updateNavButtons();
                            });
                            dropdownEl.appendChild(item);
                        });

                        dropdownEl.classList.remove('hidden');

                    } catch (_) {
                        if (loadingEl) loadingEl.classList.add('hidden');
                        dropdownEl.classList.add('hidden');
                    }
                }

                // Hide on blur
                ['originPostal', 'destPostal'].forEach(id => {
                    const el = $(id);
                    if (el) el.addEventListener('blur', () => {
                        setTimeout(() => {
                            const prefix = id.replace('Postal', '');
                            const dd = $(prefix + 'PostalDropdown');
                            const ld = $(prefix + 'PostalLoading');
                            if (dd) dd.classList.add('hidden');
                            if (ld) ld.classList.add('hidden');
                        }, 200);
                    });
                });

                // Validation-triggering inputs
                ['originPostal', 'destPostal', 'loadingDate'].forEach(id => {
                    const el = $(id);
                    if (el) el.addEventListener('input', () => {
                        state.formData[id] = el.value;
                        updateNavButtons();
                        renderStepsNav();
                        if (id !== 'loadingDate') {
                            const prefix = id.replace('Postal', '');
                            clearTimeout(el._pt);
                            el._pt = setTimeout(() => lookupPostal(prefix), 250);
                        }
                    });
                });
                ['pickupFirstName', 'pickupLastName', 'pickupEmail', 'pickupPhone',
                    'deliveryFirstName', 'deliveryLastName', 'deliveryEmail', 'deliveryPhone'
                ].forEach(id => {
                    const el = $(id);
                    if (el) el.addEventListener('input', () => {
                        state.formData[id] = el.value;
                        updateNavButtons();
                    });
                });

                // Click-outside to close dropdowns
                document.addEventListener('click', function(e) {
                    // Carrier dropdown
                    const carrierWrapper = $('carrierSearchWrapper');
                    if (carrierWrapper && !carrierWrapper.contains(e.target)) hide($('carrierDropdown'));

                    // Freight class dropdown
                    if (state.freightDropdownOpen) {
                        const fcBtn = $('freightClassBtn');
                        const fcDropdown = $('freightClassDropdown');
                        if (!fcDropdown.contains(e.target) && e.target !== fcBtn && !fcBtn.contains(e
                                .target)) closeFreightDropdown();
                    }

                    // Package type dropdowns
                    document.querySelectorAll('[id^="pkgDropdown-"]').forEach(dropdown => {
                        if (dropdown.style.display === 'none') return;
                        const idx = dropdown.id.split('-')[1];
                        const toggleBtn = document.querySelector(`[data-pkg-toggle="${idx}"]`);
                        if (!dropdown.contains(e.target) && !(toggleBtn && toggleBtn.contains(e
                                .target))) dropdown.style.display = 'none';
                    });

                    // Carrier modal click-outside
                    const modal = $('carrierModal');
                    const modalContent = $('carrierModalContent');
                    if (modal && modal.style.display !== 'none' && modalContent && !modalContent.contains(e
                            .target)) hide(modal);
                });
            }

            init();
        });
    </script>
@endpush
