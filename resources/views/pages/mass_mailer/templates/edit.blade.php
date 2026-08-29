@extends('layouts.auth')

@section('title', 'Edit Email Template')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <h1 class="text-gray-900 text-xl font-normal">Edit Email Template</h1>
                <x-common.back-button url="{{ route('mass-mailer.templates.index') }}" />
            </div>

            <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                <form method="POST" action_url="{{ route('mass-mailer.templates.update', $template->id) }}"
                    back_url="{{ route('mass-mailer.templates.index') }}" id="formSubmit" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')
                    <x-form.form-section title="Template Details" :required="true">
                        <div class="p-6 space-y-4">
                            <x-form.form-input name="name" label="Template Name" :value="$template->name" />
                            <x-form.form-input name="subject" label="Email Subject" :value="$template->subject" />

                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-slate-700">Email Body</label>
                                <textarea name="body" id="bodyEditor" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    rows="15">{{ $template->body }}</textarea>
                            </div>
                        </div>
                    </x-form.form-section>

                    <x-common.button url="{{ route('mass-mailer.templates.index') }}" type="submit" />
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.ckeditor.com/ckeditor5/35.1.0/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#bodyEditor'))
            .catch(error => {
                console.error(error);
            });
    </script>
@endsection
