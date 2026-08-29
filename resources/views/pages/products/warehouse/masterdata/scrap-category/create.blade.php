{{-- resources/views/pages/products/warehouse/masterdata/scrap-category/create.blade.php --}}
@extends('layouts.auth')

@section('title', 'Create Scrap Category')

@section('content')
    <div class="mt-4">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.masterData') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold text-black">Create Scrap Category</h2>
                            <a href="{{ route('warehouse.master-data.scrap-category.index') }}"
                                class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] w-full sm:w-auto text-center">
                                Back
                            </a>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6">
                        <form class="space-y-8" method="post"
                              action_url="{{ route('warehouse.master-data.scrap-category.store') }}"
                              back_url="{{ route('warehouse.master-data.scrap-category.index') }}"
                              id="formSubmit">
                            @csrf

                            <!-- ================= BASIC DETAILS ================= -->
                            <div>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Scrap Category Name *</label>
                                        <input type="text" name="name" id="name"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            required />
                                        <div id="name_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-4 border-t border-gray-100">
                                <a href="{{ route('warehouse.master-data.scrap-category.index') }}"
                                    class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff]">
                                    CANCEL
                                </a>
                                <button type="submit" id="submitBtn"
                                    class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                    ADD NEW
                                </button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
