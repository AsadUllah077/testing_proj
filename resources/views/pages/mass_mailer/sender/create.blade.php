@extends('layouts.auth')

@section('title', 'Send Mass Email')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <h1 class="text-gray-900 text-xl font-normal">Send Mass Email</h1>
                <x-common.back-button url="{{ route('mass-mailer.dashboard') }}" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Form Side --}}
                <div class="lg:col-span-2">
                    <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                        <form id="senderForm" class="space-y-6">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-medium text-slate-700">Select Tag (Contacts)</label>
                                    <select name="tag_id" id="tagSelect"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Choose Tag --</option>
                                        @foreach ($tags as $tag)
                                            <option value="{{ $tag->id }}" data-count="{{ $tag->contacts_count }}">
                                                {{ $tag->title }} ({{ $tag->contacts_count }} contacts)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-medium text-slate-700">Select Template</label>
                                    <select name="template_id" id="templateSelect"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Choose Template --</option>
                                        @foreach ($templates as $template)
                                            <option value="{{ $template->id }}" data-subject="{{ $template->subject }}"
                                                data-body="{{ $template->body }}">
                                                {{ $template->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <x-form.form-input name="subject" id="subjectInput" label="Email Subject"
                                    placeholder="Enter subject..." />

                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-medium text-slate-700">Email Body</label>
                                    <textarea name="body" id="bodyEditor" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                        rows="15"></textarea>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-black/10 flex justify-end">
                                <button type="button" id="startCampaignBtn"
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200">
                                    Start Sending
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Progress Side --}}
                <div>
                    <div id="progressCard"
                        class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden hidden animate-pulse-slow">
                        <div class="px-6 py-4 border-b border-black/10 bg-slate-50">
                            <h2 class="text-lg font-medium text-gray-900">Campaign Progress</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="text-center">
                                <div class="relative inline-flex items-center justify-center p-4">
                                    <svg class="w-32 h-32 transform -rotate-90">
                                        <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="8"
                                            fill="transparent" class="text-slate-100" />
                                        <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="8"
                                            fill="transparent" class="text-blue-600" id="progressCircle"
                                            stroke-dasharray="364.4" stroke-dashoffset="364.4" stroke-linecap="round" />
                                    </svg>
                                    <span class="absolute text-2xl font-bold text-slate-800"
                                        id="progressPercentage">0%</span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-600">Total Contacts</span>
                                    <span class="font-semibold text-slate-900" id="totalContacts">0</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-green-600">Sent Successfully</span>
                                    <span class="font-semibold text-green-900" id="sentCount">0</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-red-600">Failed</span>
                                    <span class="font-semibold text-red-900" id="failedCount">0</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-600">Status</span>
                                    <span class="font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800"
                                        id="campaignStatusText">Waiting...</span>
                                </div>
                            </div>

                            <div id="completionMessage" class="hidden p-4 bg-green-50 rounded-lg border border-green-200">
                                <p class="text-sm text-green-800 font-medium text-center">Campaign Finished Successfully!
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.ckeditor.com/ckeditor5/35.1.0/classic/ckeditor.js"></script>
    <script>
        let editor;
        ClassicEditor
            .create(document.querySelector('#bodyEditor'))
            .then(newEditor => {
                editor = newEditor;
            })
            .catch(error => {
                console.error(error);
            });

        // Handle Template Selection
        document.getElementById('templateSelect').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (option.value) {
                document.getElementById('subjectInput').value = option.dataset.subject;
                editor.setData(option.dataset.body);
            }
        });

        // Handle Campaign Start
        document.getElementById('startCampaignBtn').addEventListener('click', function() {
            const formData = {
                _token: '{{ csrf_token() }}',
                tag_id: document.getElementById('tagSelect').value,
                template_id: document.getElementById('templateSelect').value,
                subject: document.getElementById('subjectInput').value,
                body: editor.getData()
            };

            if (!formData.tag_id || !formData.template_id || !formData.subject || !formData.body) {
                Swal.fire('Error', 'Please fill all fields', 'error');
                return;
            }

            $(this).prop('disabled', true).addClass('opacity-50');

            $.post('{{ route('mass-mailer.sender.store') }}', formData, function(response) {
                if (response.success) {
                    Swal.fire('Campaign Started', 'Emails are being sent in the background.', 'success');
                    trackProgress(response.campaign_id);
                } else {
                    Swal.fire('Error', response.message, 'error');
                    $('#startCampaignBtn').prop('disabled', false).removeClass('opacity-50');
                }
            });
        });

        function trackProgress(campaignId) {
            document.getElementById('progressCard').classList.remove('hidden');

            const interval = setInterval(function() {
                $.get(`/mass-mailer/sender/progress/${campaignId}`, function(data) {
                    document.getElementById('totalContacts').innerText = data.total;
                    document.getElementById('sentCount').innerText = data.sent;
                    document.getElementById('failedCount').innerText = data.failed;
                    document.getElementById('progressPercentage').innerText = data.percentage + '%';
                    document.getElementById('campaignStatusText').innerText = data.status.toUpperCase();

                    // Update circle
                    const offset = 364.4 - (364.4 * data.percentage / 100);
                    document.getElementById('progressCircle').setAttribute('stroke-dashoffset', offset);

                    if (data.status === 'completed') {
                        clearInterval(interval);
                        document.getElementById('completionMessage').classList.remove('hidden');
                        document.getElementById('campaignStatusText').className =
                            "font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-800";
                        Swal.fire('Finished', 'All emails have been processed.', 'success');
                    }
                });
            }, 2000);
        }
    </script>

    <style>
        @keyframes pulse-slow {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.01);
            }
        }

        .animate-pulse-slow {
            animation: pulse-slow 3s infinite ease-in-out;
        }
    </style>
@endsection
