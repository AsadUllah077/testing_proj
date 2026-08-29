@extends('layouts.auth')

@section('title', 'Add New Tag')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <h1 class="text-gray-900 text-xl font-normal">Add New Tag</h1>
                <x-common.back-button url="{{ route('mass-mailer.tags.index') }}" />
            </div>

            <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                <form method="POST" action_url="{{ route('mass-mailer.tags.store') }}"
                    back_url="{{ route('mass-mailer.tags.index') }}" id="formSubmit" class="p-6 space-y-6">
                    @csrf
                    <x-form.form-section title="Tag Information" :required="true">
                        <div class="p-6">
                            <x-form.form-input name="title" label="Tag Title" placeholder="e.g. VIP Customers"
                                :value="old('title')" />
                        </div>
                    </x-form.form-section>

                    <x-common.button url="{{ route('mass-mailer.tags.index') }}" type="submit" />
                </form>
            </div>
        </div>
    </div>
@endsection
