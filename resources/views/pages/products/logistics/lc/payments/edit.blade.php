@extends('layouts.auth')

@section('title', 'Edit LC Payment')

@section('content')
    <div class="mt-4">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.lc') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                    <div>
                        <h1 class="text-gray-900 text-xl font-normal">Edit LC Payment -
                            {{ $payment->receipt_no ?: 'Payment #' . $payment->id }}</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Update payment receipt details</p>
                    </div>
                    <x-common.back-button url="{{ route('logistics.lc.payments.index') }}" />
                </div>

                <form method="POST" action_url="{{ route('logistics.lc.payments.update', $payment->id) }}"
                    back_url="{{ route('logistics.lc.payments.index') }}" id="formSubmit" class="space-y-4"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <x-form.form-section title="Payment Details">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-slate-700 text-sm font-semibold leading-5">Linked Booking</label>
                                <select name="booking_id"
                                    class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                    <option value="">- Select Booking -</option>
                                    @foreach ($bookings as $booking)
                                        <option value="{{ $booking->id }}"
                                            {{ old('booking_id', $payment->booking_id) == $booking->id ? 'selected' : '' }}>
                                            {{ $booking->booking_number ?: 'Booking #' . $booking->id }}
                                            @if ($booking->origin_port || $booking->destination_port)
                                                - {{ $booking->origin_port ?: '-' }} to {{ $booking->destination_port ?: '-' }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('booking_id')
                                    <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <x-form.form-input name="date" label="Date" type="date"
                                :value="old('date', $payment->date?->format('Y-m-d') ?? '')" />

                            <x-form.form-input name="receipt_no" label="Receipt No." placeholder="e.g. RCP-2026-001"
                                :value="old('receipt_no', $payment->receipt_no ?? '')" />

                            <x-form.form-select name="payment_type" label="Payment Type" placeholder=""
                                :options="[
                                    'freight_payment' => 'Freight Payment',
                                    'product_payment' => 'Product Payment',
                                ]"
                                :selected="old('payment_type', $payment->payment_type ?? 'product_payment')" :required="true" />

                            <x-form.form-select name="status" label="Status" placeholder="Select status..."
                                :options="['pending' => 'Pending', 'completed' => 'Completed']"
                                :selected="old('status', $payment->status ?? '')" />
                        </div>
                    </x-form.form-section>

                    <x-form.form-section title="Amount & Document">
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 p-6">
                            <x-form.form-input name="amount" label="Amount" type="number" step="0.01"
                                placeholder="e.g. 25000" :value="old('amount', $payment->amount ?? '')" />

                            <x-form.form-select name="currency" label="Currency" placeholder=""
                                :options="['PKR' => 'PKR', 'USD' => 'USD']"
                                :selected="old('currency', $payment->currency ?? 'PKR')" />

                            <x-form.form-input name="currency_value" label="Currency Value (PKR per 1 USD)"
                                type="number" step="0.000001" min="0.000001" placeholder="e.g. 280.000000"
                                :value="old('currency_value', $payment->currency_value ?? '')" :required="true" />

                            <div class="flex flex-col gap-1.5">
                                <label for="upload_doc" class="text-slate-700 text-sm font-semibold leading-5">Upload
                                    Doc</label>
                                <input type="file" id="upload_doc" name="upload_doc"
                                    class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#0078C8] file:text-white hover:file:bg-[#006bb0] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">

                                @if ($payment->upload_doc)
                                    <a href="{{ asset('storage/' . ltrim($payment->upload_doc, '/')) }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 text-xs text-[#0078C8] hover:underline mt-1">
                                        <i class="fa-solid fa-paperclip"></i>
                                        {{ $payment->original_filename ?: 'View current file' }}
                                    </a>
                                @else
                                    <p class="text-xs text-gray-400">PDF, image, Word, Excel, or CSV up to 10 MB.</p>
                                @endif

                                @error('upload_doc')
                                    <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </x-form.form-section>

                    <x-form.form-section title="Notes">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                            <x-form.form-textarea name="details" label="Details"
                                placeholder="Payment details, reference, or breakdown" :value="old('details', $payment->details ?? '')" :rows="4" />

                            <x-form.form-textarea name="remarks" label="Remarks"
                                placeholder="Any internal note or follow-up" :value="old('remarks', $payment->remarks ?? '')" :rows="4" />
                        </div>
                    </x-form.form-section>

                    <x-common.button url="{{ route('logistics.lc.payments.index') }}" type="submit" />
                </form>
            </div>
        </div>
    </div>
@endsection
