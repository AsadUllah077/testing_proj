@extends('layouts.auth')

@section('title', 'Add New Contact')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <h1 class="text-gray-900 text-xl font-normal">Add New Contact</h1>
                <x-common.back-button url="{{ route('mass-mailer.contacts.index') }}" />
            </div>

            <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                <form method="POST" action_url="{{ route('mass-mailer.contacts.store') }}"
                    back_url="{{ route('mass-mailer.contacts.index') }}" id="formSubmit" class="p-6 space-y-6">
                    @csrf
                    <x-form.form-section title="Basic Information" :required="true">
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-form.form-input name="firstname" label="First Name" placeholder="First Name" />
                            <x-form.form-input name="lastname" label="Last Name" placeholder="Last Name" />
                            <x-form.form-input name="email" label="Email Address" type="email"
                                placeholder="email@example.com" />
                            <x-form.form-input name="phone" label="Phone Number" placeholder="Phone Number" />
                        </div>
                    </x-form.form-section>

                    <x-form.form-section title="Professional Information" :required="false">
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-form.form-input name="company" label="Company Name" placeholder="Company Name" />
                            <x-form.form-input name="position" label="Position" placeholder="Position" />
                        </div>
                    </x-form.form-section>

                    <x-form.form-section title="Address" :required="false">
                        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <x-form.form-input name="city" label="City" placeholder="City" />
                            <x-form.form-input name="state" label="State" placeholder="State" />
                            <x-form.form-input name="country" label="Country" placeholder="Country" />
                            <div class="md:col-span-3">
                                <x-form.form-input name="address" label="Full Address" placeholder="Street layout" />
                            </div>
                        </div>
                    </x-form.form-section>

                    <x-form.form-section title="Tags & Notes" :required="true">
                        <div class="p-6 space-y-4">
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-slate-700">Select Tags</label>
                                <select name="tag_ids[]" multiple
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    style="min-height: 120px;">
                                    @foreach ($tags as $tag)
                                        <option value="{{ $tag->id }}">{{ $tag->title }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-slate-500 mt-1">Hold Ctrl (Cmd) to select multiple tags.</p>
                            </div>
                            <x-form.form-input name="note" label="Notes" type="textarea"
                                placeholder="Any additional information..." />
                        </div>
                    </x-form.form-section>

                    <x-common.button url="{{ route('mass-mailer.contacts.index') }}" type="submit" />
                </form>
            </div>
        </div>
    </div>
@endsection
