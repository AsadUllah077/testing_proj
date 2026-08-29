@extends('layouts.auth')

@section('title', 'Statutory Filing Calendar')

@section('content')
    <div class="min-h-screen bg-[#f8fafc] pb-12">
        {{-- Header --}}
        <div class="bg-white border-b border-gray-200 shadow-sm relative z-20">
            <div class="mx-auto px-6 py-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">

                    <!-- Left: Title -->
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Filing Calendar</h1>
                        <p class="text-sm text-gray-500 mt-1">Global view of all statutory deadlines and tax payment windows.
                        </p>
                    </div>

                    <!-- Right: Actions -->
                    <div class="flex items-center gap-3">
                        <select id="statutoryBodyFilter"
                            class="bg-white border border-gray-200 text-gray-700 text-xs font-bold rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition outline-none cursor-pointer uppercase tracking-wider">
                            <option value="">All Authorities</option>
                            @foreach ($statutoryBodies as $body)
                                <option value="{{ $body->id }}">{{ $body->name }} ({{ $body->code }})</option>
                            @endforeach
                        </select>
                        <select id="statusFilter"
                            class="bg-white border border-gray-200 text-gray-700 text-xs font-bold rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition outline-none cursor-pointer uppercase tracking-wider">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="overdue">Overdue</option>
                        </select>

                        <a href="{{ route('payroll.compliance.dashboard') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 transition shadow-sm">
                            ← Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="mx-auto px-6 mt-8 flex flex-col lg:flex-row gap-8">
            {{-- Calendar Card --}}
            <div class="flex-1 bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden p-6 relative z-10">
                <div id="calendar"></div>
            </div>

            {{-- Side Detail Panel --}}
            <div id="sidePanel" class="w-full lg:w-96 space-y-6 hidden lg:block">
                <div class="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden p-8 sticky top-8">
                    <div id="emptyPanelState" class="text-center py-12">
                        <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Selection Required</h3>
                        <p class="text-xs text-gray-400 mt-2">Click an event on the calendar to view full obligation details
                            and record payments.</p>
                    </div>

                    <div id="eventDetails" class="hidden">
                        <div class="flex justify-between items-start mb-6">
                            <span id="detailStatus"
                                class="px-2 py-1 bg-blue-50 text-blue-600 text-[10px] font-black rounded uppercase tracking-tighter">Pending</span>
                            <span id="detailCode"
                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest">CODE</span>
                        </div>
                        <h2 id="detailTitle" class="text-xl font-black text-gray-900 leading-tight">Obligation Title</h2>
                        <p id="detailBody" class="text-xs text-gray-500 mt-2 font-medium">Authority Full Name</p>

                        <div class="mt-8 space-y-4">
                            <div class="flex justify-between items-center py-3 border-b border-gray-50">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Due Date</span>
                                <span id="detailDueDate" class="text-xs font-black text-gray-800">Oct 15, 2026</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-50">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Amount
                                    Due</span>
                                <span id="detailAmount" class="text-lg font-black text-blue-600">{{ currency()?->symbol }}
                                    0.00</span>
                            </div>
                            <div id="penaltyRow"
                                class="hidden flex justify-between items-center py-3 border-b border-gray-50">
                                <span class="text-[10px] font-bold text-red-400 uppercase tracking-widest italic">Est.
                                    Penalty</span>
                                <span id="detailPenalty" class="text-xs font-black text-red-600">{{ currency()?->symbol }}
                                    0.00</span>
                            </div>
                        </div>

                        <div class="mt-8">
                            <button id="markCompletedBtn"
                                class="w-full py-4 bg-blue-600 text-white text-xs font-black rounded-2xl hover:bg-blue-700 transition shadow-lg shadow-blue-100 uppercase tracking-widest">
                                Record Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Detail Modal --}}
    <div id="mobileModal"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-[100] items-end justify-center lg:hidden p-0">
        <div class="bg-white w-full rounded-t-[40px] p-8 shadow-2xl transform translate-y-full transition-transform duration-300 overflow-y-auto max-h-[80vh]"
            id="mobileModalContent">
            <div class="w-12 h-1 bg-gray-100 rounded-full mx-auto mb-6"></div>
            <div id="mobileDetails"></div>
            <button onclick="closeMobileModal()"
                class="w-full mt-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest border border-gray-100 rounded-2xl">Close</button>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <style>
        .fc-theme-standard td,
        .fc-theme-standard th {
            border-color: #f1f5f9;
        }

        .fc-theme-standard .fc-scrollgrid {
            border-color: #f1f5f9;
            border-radius: 20px;
            overflow: hidden;
        }

        .fc-header-toolbar {
            margin-bottom: 2rem !important;
        }

        .fc-button-primary {
            background: #fff !important;
            border: 1px solid #e2e8f0 !important;
            color: #64748b !important;
            font-size: 11px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            padding: 10px 20px !important;
            border-radius: 12px !important;
        }

        .fc-button-primary:hover {
            background: #f8fafc !important;
            color: #1e293b !important;
        }

        .fc-button-active {
            background: #3b82f6 !important;
            border-color: #3b82f6 !important;
            color: #fff !important;
        }

        .fc-toolbar-title {
            font-weight: 900 !important;
            color: #1e293b !important;
            font-size: 1.25rem !important;
        }

        .fc-day-today {
            background: #f0f7ff !important;
        }

        .fc-event {
            border-radius: 6px !important;
            padding: 2px 6px !important;
            font-size: 10px !important;
            font-weight: 800 !important;
            border: none !important;
        }

        .fc-daygrid-day-number {
            font-size: 11px !important;
            font-weight: 800 !important;
            color: #94a3b8 !important;
            padding: 10px !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        let calendar = null;
        let selectedEventId = null;

        $(document).ready(function() {
            initializeCalendar();

            $('#statutoryBodyFilter, #statusFilter').on('change', () => {
                if (calendar) calendar.refetchEvents();
            });

            $('#markCompletedBtn').click(function() {
                if (!selectedEventId) return;
                const btn = $(this);
                btn.prop('disabled', true).text('PROCESSING...');

                $.post(`/payroll/compliance/events/${selectedEventId}/complete`, {
                        _token: '{{ csrf_token() }}'
                    })
                    .done(res => {
                        toastr.success(res.message);
                        if (calendar) calendar.refetchEvents();
                        resetPanel();
                    })
                    .always(() => btn.prop('disabled', false).text('RECORD PAYMENT'));
            });
        });

        function initializeCalendar() {
            const calendarEl = document.getElementById('calendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 750,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listWeek'
                },
                events: function(fetchInfo, successCallback, failureCallback) {
                    const filters = {
                        start_date: fetchInfo.startStr.substring(0, 10),
                        end_date: fetchInfo.endStr.substring(0, 10),
                        statutory_body_id: $('#statutoryBodyFilter').val(),
                        status: $('#statusFilter').val(),
                        ajax: 1
                    };
                    $.get('{{ route('payroll.compliance.calendar') }}', filters)
                        .done(data => successCallback(data.events))
                        .fail(err => failureCallback(err));
                },
                eventClick: function(info) {
                    showDetails(info.event);
                }
            });
            calendar.render();
        }

        function showDetails(event) {
            const props = event.extendedProps;
            selectedEventId = event.id;

            // Update Desktop Panel
            $('#emptyPanelState').addClass('hidden');
            $('#eventDetails').removeClass('hidden');

            $('#detailTitle').text(event.title);
            $('#detailBody').text(props.statutory_body);
            $('#detailCode').text(props.statutory_body.substring(0, 5).toUpperCase());
            $('#detailDueDate').text(new Date(event.start).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            }));
            $('#detailAmount').text("{{ currency()?->symbol }} " + new Intl.NumberFormat().format(props.amount_due || 0));

            let statusHtml =
                '<span class="px-2 py-1 bg-blue-50 text-blue-600 text-[10px] font-black rounded uppercase tracking-tighter">Pending</span>';
            if (props.status === 'completed') {
                statusHtml =
                    '<span class="px-2 py-1 bg-green-50 text-green-600 text-[10px] font-black rounded uppercase tracking-tighter">Completed</span>';
                $('#markCompletedBtn').addClass('hidden');
            } else if (props.is_overdue) {
                statusHtml =
                    '<span class="px-2 py-1 bg-red-50 text-red-600 text-[10px] font-black rounded uppercase tracking-tighter animate-pulse">Overdue</span>';
                $('#markCompletedBtn').removeClass('hidden');
            } else {
                $('#markCompletedBtn').removeClass('hidden');
            }
            $('#detailStatus').html(statusHtml);

            // Mobile Handing
            if (window.innerWidth < 1024) {
                openMobileModal();
            }
        }

        function resetPanel() {
            $('#emptyPanelState').removeClass('hidden');
            $('#eventDetails').addClass('hidden');
            selectedEventId = null;
        }

        function openMobileModal() {
            $('#mobileModal').removeClass('hidden').addClass('flex');
            setTimeout(() => $('#mobileModalContent').removeClass('translate-y-full'), 10);
            $('#mobileDetails').html($('#eventDetails').html());
            // Fix for ID duplication in mobile view
            $('#mobileDetails #markCompletedBtn').attr('id', 'mobileMarkCompletedBtn').click(function() {
                $('#markCompletedBtn').click();
                closeMobileModal();
            });
        }

        function closeMobileModal() {
            $('#mobileModalContent').addClass('translate-y-full');
            setTimeout(() => $('#mobileModal').addClass('hidden').removeClass('flex'), 300);
        }
    </script>
@endpush
