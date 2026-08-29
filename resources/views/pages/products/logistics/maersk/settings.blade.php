@extends('layouts.auth')
@section('title', 'Maersk API Settings')
@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <!-- Header -->
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Maersk API Settings</h2>
                    <p class="text-xs text-slate-500 mt-1">Configure your credentials from the <a
                            href="https://developer.maersk.com" target="_blank"
                            class="text-[#0078C8] underline">Maersk Developer Portal</a></p>
                </div>
                <a href="{{ route('logistics.maersk.index') }}"
                    class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-normal rounded hover:bg-slate-50 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Back to Hub
                </a>
            </div>

            <div class="mt-4 grid grid-cols-1 xl:grid-cols-3 gap-4">
                <!-- Settings Form -->
                <div class="xl:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <h3 class="font-semibold text-slate-800 text-sm">API Credentials</h3>
                        <p class="text-xs text-slate-500 mt-0.5">These credentials are stored per company.</p>
                    </div>
                    <form id="settingsForm" class="p-6 space-y-5">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-2">Consumer
                                Key <span class="text-red-500">*</span></label>
                            <input type="text" name="consumer_key" id="consumer_key"
                                value="{{ $setting->consumer_key }}"
                                placeholder="Your Maersk Consumer Key"
                                class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm font-mono">
                            <p class="mt-1 text-xs text-slate-400">Found in the Maersk Developer Portal under your app's credentials.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-2">Consumer
                                Secret <span class="text-red-500">*</span></label>
                            <input type="password" name="consumer_secret" id="consumer_secret"
                                value="{{ $setting->consumer_secret }}"
                                placeholder="Your Maersk Consumer Secret"
                                class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-2">Base
                                URL</label>
                            <select name="base_url" id="base_url"
                                class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                <option value="https://api.maersk.com"
                                    {{ ($setting->base_url ?? 'https://api.maersk.com') === 'https://api.maersk.com' ? 'selected' : '' }}>
                                    Production — api.maersk.com</option>
                                <option value="https://api-stage.maersk.com"
                                    {{ ($setting->base_url ?? '') === 'https://api-stage.maersk.com' ? 'selected' : '' }}>
                                    Pre-Production (Stage) — api-stage.maersk.com</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit"
                                class="px-5 py-2 bg-[#0078C8] text-white text-xs font-semibold rounded hover:bg-[#006bb0] flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk"></i> Save Settings
                            </button>
                            <button type="button" id="testBtn"
                                class="px-5 py-2 border border-slate-300 text-slate-700 text-xs font-semibold rounded hover:bg-slate-50 flex items-center gap-2">
                                <i class="fa-solid fa-plug"></i> Test Connection
                            </button>
                        </div>
                        <div id="testResult" class="hidden"></div>
                    </form>
                </div>

                <!-- Info Panel -->
                <div class="space-y-4">
                    <div class="bg-[#00243D] text-white rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fa-solid fa-ship text-lg"></i>
                            <span class="font-bold text-sm">Maersk Developer Portal</span>
                        </div>
                        <p class="text-xs text-blue-200 leading-relaxed">To get API credentials, register or sign in at
                            <strong>developer.maersk.com</strong>, create an application, and subscribe to the APIs you
                            need.</p>
                        <div class="mt-3 pt-3 border-t border-white/20 space-y-1.5">
                            <div class="flex items-center gap-2 text-xs text-blue-100">
                                <i class="fa-solid fa-check-circle text-emerald-400 w-4"></i>
                                Consumer Key = Client ID
                            </div>
                            <div class="flex items-center gap-2 text-xs text-blue-100">
                                <i class="fa-solid fa-check-circle text-emerald-400 w-4"></i>
                                Token auto-refreshes on expiry
                            </div>
                            <div class="flex items-center gap-2 text-xs text-blue-100">
                                <i class="fa-solid fa-check-circle text-emerald-400 w-4"></i>
                                Tokens valid for ~1 hour
                            </div>
                        </div>
                    </div>

                    @if ($setting->access_token)
                        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                            <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Token Status</h4>
                            @if ($setting->token_expires_at && $setting->token_expires_at->isFuture())
                                <div class="flex items-center gap-2 text-emerald-600">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span class="text-xs font-medium">Active</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Expires {{ $setting->token_expires_at->diffForHumans() }}</p>
                            @else
                                <div class="flex items-center gap-2 text-amber-500">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span class="text-xs font-medium">Expired — will auto-refresh</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type=submit]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

            fetch('{{ route('logistics.maersk.settings.save') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        consumer_key: document.getElementById('consumer_key').value,
                        consumer_secret: document.getElementById('consumer_secret').value,
                        base_url: document.getElementById('base_url').value,
                    })
                })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Settings';
                    const msg = document.getElementById('testResult');
                    msg.className = data.success
                        ? 'mt-3 p-3 rounded bg-emerald-50 border border-emerald-200 text-sm text-emerald-700'
                        : 'mt-3 p-3 rounded bg-red-50 border border-red-200 text-sm text-red-700';
                    msg.textContent = data.message;
                    msg.classList.remove('hidden');
                    setTimeout(() => msg.classList.add('hidden'), 5000);
                });
        });

        document.getElementById('testBtn').addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Testing...';
            const msg = document.getElementById('testResult');

            fetch('{{ route('logistics.maersk.settings.test') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = '<i class="fa-solid fa-plug"></i> Test Connection';
                    msg.className = data.success
                        ? 'mt-3 p-3 rounded bg-emerald-50 border border-emerald-200 text-sm text-emerald-700 flex items-center gap-2'
                        : 'mt-3 p-3 rounded bg-red-50 border border-red-200 text-sm text-red-700 flex items-center gap-2';
                    msg.innerHTML = (data.success ? '<i class="fa-solid fa-circle-check"></i>' :
                            '<i class="fa-solid fa-circle-xmark"></i>') +
                        ' ' + data.message;
                    msg.classList.remove('hidden');
                });
        });
    </script>
@endpush
