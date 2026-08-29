@extends('layouts.auth')

@section('title', 'Purchase Order #' . $purchaseOrder->po_number)

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3 pb-12">
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-file-invoice-dollar text-[#0078C8] text-xl"></i>
                    <h1 class="text-gray-900 text-xl font-normal">Purchase Order <span
                            class="font-bold">#{{ $purchaseOrder->po_number }}</span></h1>
                    <span
                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold uppercase">{{ $purchaseOrder->status }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="window.print()"
                        class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-semibold hover:bg-slate-50 flex items-center gap-2">
                        <i class="fa-solid fa-print"></i> PRINT
                    </button>
                    <x-common.back-button url="{{ route('logistics.purchase-orders.index') }}" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {{-- Main PO Content --}}
                <div class="lg:col-span-8 space-y-6">
                    {{-- Summary Card --}}
                    <div id="printable-invoice" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden p-8">
                        <div class="flex justify-between items-start mb-10">
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter mb-1">PURCHASE
                                    ORDER</h2>
                                <p class="text-slate-400 text-sm font-medium">Logistics Department</p>
                            </div>
                            <div class="text-right">
                                <p class="text-slate-400 text-xs font-bold uppercase mb-1">Date</p>
                                <p class="text-slate-900 font-bold">
                                    {{ \Carbon\Carbon::parse($purchaseOrder->order_date)->format('M d, Y') }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-12 mb-10">
                            <div>
                                <p class="text-slate-400 text-xs font-bold uppercase mb-3 px-1 border-l-4 border-[#0078C8]">
                                    Supplier Details</p>
                                <h4 class="text-lg font-bold text-slate-900 mb-1">
                                    {{ $purchaseOrder->supplier->company_name }}
                                </h4>
                                <p class="text-sm text-slate-500 leading-relaxed">
                                    {{ $purchaseOrder->supplier->city ?? '-' }}<br>
                                    {{ $purchaseOrder->supplier->country }}
                                </p>
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <p class="text-slate-400 text-xs font-bold uppercase mb-1">Payment Terms</p>
                                    <p class="text-slate-900 font-semibold">{{ $purchaseOrder->payment_terms }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-400 text-xs font-bold uppercase mb-1">Expected Delivery</p>
                                    <p class="text-slate-900 font-semibold">
                                        {{ $purchaseOrder->expected_delivery_date ? \Carbon\Carbon::parse($purchaseOrder->expected_delivery_date)->format('M d, Y') : '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-slate-400 text-xs font-bold uppercase mb-1">Currency</p>
                                    <p class="text-slate-900 font-semibold">{{ $purchaseOrder->currency }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-400 text-xs font-bold uppercase mb-1">Created By</p>
                                    <p class="text-slate-900 font-semibold">{{ $purchaseOrder->creator->name ?? 'System' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <table class="w-full text-left mb-8">
                            <thead>
                                <tr class="border-b-2 border-slate-100">
                                    <th class="py-4 px-2 text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                        SKU</th>
                                    <th class="py-4 px-2 text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                        Description</th>
                                    <th
                                        class="py-4 px-2 text-[11px] font-black text-slate-400 uppercase tracking-widest text-center">
                                        QTY</th>
                                    <th
                                        class="py-4 px-2 text-[11px] font-black text-slate-400 uppercase tracking-widest text-right">
                                        Price</th>
                                    <th
                                        class="py-4 px-2 text-[11px] font-black text-slate-400 uppercase tracking-widest text-right">
                                        Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach ($purchaseOrder->items as $item)
                                    <tr>
                                        <td class="py-5 px-2 text-sm font-bold text-slate-900">{{ $item->sku }}</td>
                                        <td class="py-5 px-2 text-sm text-slate-500">{{ $item->description }}</td>
                                        <td class="py-5 px-2 text-sm font-bold text-slate-900 text-center">
                                            {{ $item->quantity }}</td>
                                        <td class="py-5 px-2 text-sm text-slate-600 text-right">
                                            {{ number_format($item->price, 2) }}</td>
                                        <td class="py-5 px-2 text-sm font-black text-slate-900 text-right">
                                            {{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="flex justify-end">
                            <div class="w-64 space-y-3">
                                <div class="flex justify-between text-sm py-1">
                                    <span class="text-slate-400 font-bold uppercase">Subtotal</span>
                                    <span
                                        class="text-slate-900 font-bold">{{ number_format($purchaseOrder->subtotal, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm py-1">
                                    <span class="text-slate-400 font-bold uppercase">Tax Total</span>
                                    <span
                                        class="text-slate-900 font-bold">{{ number_format($purchaseOrder->tax_amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between py-3 border-t-2 border-slate-900">
                                    <span class="text-lg font-black text-slate-900 uppercase">Grand Total</span>
                                    <span class="text-2xl font-black text-[#0078C8]">{{ $purchaseOrder->currency }}
                                        {{ number_format($purchaseOrder->total_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        @if ($purchaseOrder->notes)
                            <div class="mt-12 p-6 bg-slate-50 rounded-xl border border-slate-100">
                                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Terms & Notes
                                </p>
                                <p class="text-sm text-slate-600 leading-relaxed">{{ $purchaseOrder->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Side Info --}}
                <div class="lg:col-span-4 space-y-6">
                    {{-- Attachments --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/10 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-paperclip text-[#0078C8]"></i>
                                <h3 class="font-bold text-sm text-slate-800 uppercase tracking-wider">Attachments</h3>
                            </div>
                            <span
                                class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-bold">{{ $purchaseOrder->attachments->count() }}</span>
                        </div>
                        <div class="p-4 space-y-3">
                            @forelse($purchaseOrder->attachments as $attachment)
                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank"
                                    class="flex items-center gap-3 p-3 border border-slate-100 rounded-lg hover:border-[#0078C8] hover:bg-blue-50/30 transition-all group">
                                    <div
                                        class="w-10 h-10 bg-slate-50 rounded flex items-center justify-center group-hover:bg-[#0078C8] group-hover:text-white transition-colors">
                                        @if (Str::contains($attachment->file_type, 'pdf'))
                                            <i class="fa-solid fa-file-pdf text-lg"></i>
                                        @else
                                            <i class="fa-solid fa-file-image text-lg"></i>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-slate-700 truncate capitalize">
                                            {{ $attachment->file_name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ round($attachment->file_size / 1024, 2) }}
                                            KB</p>
                                    </div>
                                    <i class="fa-solid fa-download text-slate-300 group-hover:text-[#0078C8] text-xs"></i>
                                </a>
                            @empty
                                <div class="text-center py-6">
                                    <i class="fa-solid fa-box-open text-2xl text-slate-100 mb-2"></i>
                                    <p class="text-xs text-slate-400 font-medium">No attachments uploaded</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Activity / Status Histoy (Placeholder) --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/10">
                            <h3 class="font-bold text-sm text-slate-800 uppercase tracking-wider">History</h3>
                        </div>
                        <div class="p-5">
                            <div class="relative pl-6 border-l-2 border-slate-100 py-1">
                                <span
                                    class="absolute -left-[9px] top-2 w-4 h-4 rounded-full bg-blue-500 border-4 border-white"></span>
                                <p class="text-xs font-bold text-slate-900 uppercase">PO CREATED</p>
                                <p class="text-[10px] text-slate-400 font-medium mt-0.5">
                                    {{ $purchaseOrder->created_at->format('M d, Y - H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('styles')
        <style>
            @media print {
                /* Hide everything except the printable area */
                body * {
                    visibility: hidden;
                }
                #printable-invoice, #printable-invoice * {
                    visibility: visible;
                }
                /* Reset position and size for printing */
                #printable-invoice {
                    position: absolute !important;
                    left: 0 !important;
                    top: 0 !important;
                    width: 100% !important;
                    border: none !important;
                    box-shadow: none !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    background: white !important;
                }
                /* Force background colors to print */
                #printable-invoice {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                /* Hide buttons, nav, and side content explicitly */
                header, nav, aside, footer, .lg\:col-span-4, .back-btn, button, .flex.items-center.justify-between.mb-4 {
                    display: none !important;
                }
                /* Page setup */
                @page {
                    size: auto;
                    margin: 10mm;
                }
                /* Enhance typography for print */
                .text-slate-400 { color: #64748b !important; }
                .text-slate-900 { color: #0f172a !important; }
                .bg-slate-50 { background-color: #f8fafc !important; border: 1px solid #e2e8f0 !important; }
                .text-[#0078C8] { color: #0078C8 !important; }
            }
        </style>
    @endpush
@endsection
