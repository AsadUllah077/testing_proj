@extends('layouts.auth')

@section('title', 'Compliance Alert Center')

@section('content')
    <div class="min-h-screen bg-[#f8fafc] pb-12">
        {{-- Header --}}
        <div class="bg-white border-b border-gray-200">
            <div class="mx-auto px-6 py-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">

                    <!-- Left: Title -->
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Compliance Alert Center</h1>
                        <p class="text-sm text-gray-500 mt-1">Audit logs for regulatory changes, upcoming deadlines, and
                            penalty warnings.</p>
                    </div>

                    <!-- Right: Actions -->
                    <div class="flex items-center gap-3">
                        <select id="severityFilter"
                            class="bg-white border border-gray-200 text-gray-700 text-xs font-bold rounded-xl px-4 py-2.5 outline-none cursor-pointer uppercase tracking-wider">
                            <option value="">All Severities</option>
                            <option value="critical">Critical</option>
                            <option value="error">Error</option>
                            <option value="warning">Warning</option>
                            <option value="info">Info</option>
                        </select>
                        <select id="statusFilter"
                            class="bg-white border border-gray-200 text-gray-700 text-xs font-bold rounded-xl px-4 py-2.5 outline-none cursor-pointer uppercase tracking-wider">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="acknowledged">Acknowledged</option>
                        </select>

                        <a href="{{ route('payroll.compliance.dashboard') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 transition shadow-sm">
                            ← Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto px-6 mt-8">
            {{-- Feed-style Alert Container --}}
            <div id="alertsContainer" class="space-y-4">
                {{-- Alerts will be injected here --}}
                <div class="text-center py-20">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="text-xs text-gray-400 mt-4 font-bold uppercase tracking-widest">Scanning Regulatory Records...
                    </p>
                </div>
            </div>

            <div id="pagination-wrapper" class="mt-8">
                {{-- Pagination injected here --}}
            </div>
        </div>
    </div>

    {{-- Alert Template (Hidden) --}}
    <template id="alertTemplate">
        <div
            class="alert-item group bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg transition-all duration-300 relative overflow-hidden">
            <div class="severity-bar absolute left-0 top-0 bottom-0 w-1.5"></div>
            <div class="flex gap-6 items-start">
                <div class="icon-container shrink-0 w-12 h-12 rounded-xl flex items-center justify-center text-xl"></div>
                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-3">
                                <h3 class="title text-sm font-black text-gray-900 tracking-tight">Alert Title</h3>
                                <span
                                    class="severity-badge text-[8px] font-black uppercase px-2 py-0.5 rounded tracking-tighter">Severity</span>
                            </div>
                            <p class="message text-xs text-gray-500 mt-1 leading-relaxed max-w-2xl">Alert message content
                                goes here.</p>
                        </div>
                        <div class="text-right">
                            <p class="time text-[10px] font-bold text-gray-400 uppercase tracking-widest">Time</p>
                            <p class="status text-[10px] font-black mt-1 uppercase tracking-tighter">Active</p>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-50 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Related
                                Event:</span>
                            <span
                                class="event-title text-[10px] font-black text-blue-600 hover:underline cursor-pointer">N/A</span>
                        </div>
                        <div class="actions flex gap-3">
                            <button
                                class="acknowledge-btn text-[10px] font-black text-blue-600 hover:text-blue-800 uppercase tracking-widest transition">
                                Acknowledge Alert
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            loadAlerts();

            $('#statusFilter, #severityFilter').change(() => loadAlerts());

            $(document).on('click', '.acknowledge-btn', function() {
                const id = $(this).data('id');
                const btn = $(this);
                btn.prop('disabled', true).text('PROCESSING...');

                $.post(`/payroll/compliance/alerts/${id}/acknowledge`, {
                        _token: '{{ csrf_token() }}'
                    })
                    .done(res => {
                        toastr.success(res.message);
                        loadAlerts();
                    });
            });
        });

        function loadAlerts() {
            const filters = {
                status: $('#statusFilter').val(),
                severity: $('#severityFilter').val(),
                ajax: 1
            };

            $.get('{{ route('payroll.compliance.alerts') }}', filters)
                .done(data => {
                    const container = $('#alertsContainer');
                    if (!data.alerts || data.alerts.data.length === 0) {
                        container.html(
                            '<div class="bg-white rounded-3xl p-20 text-center border border-gray-100 shadow-sm"><div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">🛡️</div><h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest">All Clear</h3><p class="text-xs text-gray-400 mt-2">No active compliance alerts or penalty warnings detected.</p></div>'
                        );
                        return;
                    }

                    container.html(data.alerts.data.map(alert => {
                        const temp = document.getElementById('alertTemplate').content.cloneNode(true);

                        const severityMap = {
                            'critical': {
                                color: 'bg-red-500',
                                bg: 'bg-red-50',
                                text: 'text-red-600',
                                icon: '☣️'
                            },
                            'error': {
                                color: 'bg-orange-500',
                                bg: 'bg-orange-50',
                                text: 'text-orange-600',
                                icon: '⚠️'
                            },
                            'warning': {
                                color: 'bg-amber-500',
                                bg: 'bg-amber-50',
                                text: 'text-amber-600',
                                icon: '🔔'
                            },
                            'info': {
                                color: 'bg-blue-500',
                                bg: 'bg-blue-50',
                                text: 'text-blue-600',
                                icon: 'ℹ️'
                            }
                        };
                        const s = severityMap[alert.severity] || severityMap.info;

                        $(temp).find('.severity-bar').addClass(s.color);
                        $(temp).find('.icon-container').addClass(s.bg + ' ' + s.text).text(s.icon);
                        $(temp).find('.title').text(alert.title);
                        $(temp).find('.severity-badge').addClass(s.bg + ' ' + s.text).text(alert.severity);
                        $(temp).find('.message').text(alert.message);
                        $(temp).find('.time').text(new Date(alert.created_at).toLocaleString());
                        $(temp).find('.status').text(alert.status).addClass(alert.status === 'active' ?
                            'text-amber-600' : 'text-green-600');
                        $(temp).find('.event-title').text(alert.compliance_event?.title || 'System Alert');

                        if (alert.status === 'acknowledged') {
                            $(temp).find('.acknowledge-btn').addClass('hidden');
                        } else {
                            $(temp).find('.acknowledge-btn').data('id', alert.id);
                        }

                        return temp;
                    }));

                    $('#pagination-wrapper').html(data.pagination);
                });
        }
    </script>
@endpush
