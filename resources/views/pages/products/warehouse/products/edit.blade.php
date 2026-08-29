@extends('layouts.auth')

@section('title', 'Update Product')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                <!-- Header -->
                <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-black">Update Product</h2>
                        <a href="{{ route('warehouse.products.index') }}"
                            class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                            Back
                        </a>
                    </div>
                </div>

                <!-- Main Content Card -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    @include('pages.products.warehouse.products.partials.edit_form')
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('pages.products.warehouse.products.partials.edit_scripts')
@endpush
