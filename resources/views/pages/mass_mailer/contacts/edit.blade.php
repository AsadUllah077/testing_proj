@extends('layouts.auth')

@section('title', 'Edit Contact')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <h1 class="text-gray-900 text-xl font-normal">Edit Contact</h1>
                <x-common.back-button url="{{ route('mass-mailer.contacts.index') }}" />
            </div>

            <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                <form method="POST" action_url="{{ route('mass-mailer.contacts.update', $contact->id) }}"
                    back_url="{{ route('mass-mailer.contacts.index') }}" id="formSubmit" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')
                    <x-form.form-section title="Basic Information" :required="true">
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-form.form-input name="firstname" label="First Name" placeholder="First Name"
                                :value="$contact->firstname" />
                            <x-form.form-input name="lastname" label="Last Name" placeholder="Last Name"
                                :value="$contact->lastname" />
                            <x-form.form-input name="email" label="Email Address" type="email"
                                placeholder="email@example.com" :value="$contact->email" />
                            <x-form.form-input name="phone" label="Phone Number" placeholder="Phone Number"
                                :value="$contact->phone" />
                        </div>
                    </x-form.form-section>

                    <x-form.form-section title="Tags & Notes" :required="true">
                        <div class="p-6 space-y-4">
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-slate-700">Select Tags</label>
                                @php $selectedTags = $contact->tags->pluck('id')->toArray(); @endphp
                                <select name="tag_ids[]" multiple
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    style="min-height: 120px;">
                                    @foreach ($tags as $tag)
                                        <option value="{{ $tag->id }}"
                                            {{ in_array($tag->id, $selectedTags) ? 'selected' : '' }}>{{ $tag->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <x-form.form-input name="note" label="Notes" type="textarea" placeholder="Any additional..."
                                :value="$contact->note" />
                        </div>
                    </x-form.form-section>

                    <x-common.button url="{{ route('mass-mailer.contacts.index') }}" type="submit" />
                </form>
            </div>
        </div>
    </div>
@endsection
