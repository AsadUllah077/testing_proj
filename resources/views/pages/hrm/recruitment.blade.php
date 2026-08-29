@extends('layouts.auth')
@section('title')
    Recruit Dashboard
@endsection

@section('content')
    <div class="p-4 bg-gray-50 min-h-screen">
        <!-- Module Tabs -->
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.recruitment') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Recruit Pro</h1>
                    <p class="text-gray-600 mt-1">Track your hiring pipeline and key metrics</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700 font-medium">{{ Auth::user()->email ?? 'aim.hover822' }}</span>
                    <div class="w-10 h-10 bg-[#0078C8] rounded-full flex items-center justify-center">
                        <span class="text-white font-medium">
                            {{ strtoupper(substr(Auth::user()->first_name, 0, 1) . substr(Auth::user()->last_name, 0, 1)) ?? 'AH' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3 Cards Section - Recruitment Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Active Jobs -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Active Jobs</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $activeJobs }}</p>
                        <div class="flex items-center mt-2">
                            <span class="text-xs {{ $jobsChange >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                {{ $jobsChange >= 0 ? '▲' : '▼' }} {{ abs($jobsChange) }}% week
                            </span>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- New Applications -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">New Applications</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $newApplications }}</p>
                        <div class="flex items-center mt-2">
                            <span class="text-xs {{ $appChange >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                {{ $appChange >= 0 ? '▲' : '▼' }} {{ abs($appChange) }}% last month
                            </span>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Interviews Today -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Interviews Today</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $interviewsToday }}</p>
                        <div class="flex items-center mt-2">
                            <span class="text-xs text-green-600 font-medium">▼ {{ $avgTimeToHire }} days (improved)</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Grid - 2 Columns -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column - Full width for Candidate Pipeline -->
            <div>
                <!-- Candidate Pipeline Card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                    <div class="flex justify-between items-center mb-8">
                        <h3 class="text-lg font-semibold text-gray-900">Candidate Pipeline</h3>
                        <button class="text-sm text-[#0078C8] hover:text-[#0066B3] font-medium">
                            View Report
                        </button>
                    </div>

                    <!-- Pipeline Stages -->
                    <div class="space-y-6">
                        @php
                            $pipelineStages = [
                                'applied' => ['color' => 'bg-blue-600', 'label' => 'Applied'],
                                'screening' => ['color' => 'bg-blue-500', 'label' => 'Screening'],
                                'interview' => ['color' => 'bg-blue-400', 'label' => 'Interview'],
                                'offer' => ['color' => 'bg-blue-300', 'label' => 'Offer'],
                                'hired' => ['color' => 'bg-green-500', 'label' => 'Hired'],
                            ];
                        @endphp

                        @foreach ($pipelineStages as $stage => $data)
                            <div data-stage="{{ $stage }}">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-700">{{ $data['label'] }}</span>
                                    <span
                                        class="text-sm text-gray-500 candidate-count">{{ $pipelineStats[$stage]['count'] ?? 0 }}
                                        Candidates</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="{{ $data['color'] }} h-2 rounded-full progress-bar"
                                        style="width: {{ $pipelineStats[$stage]['percentage'] ?? 0 }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Column - Full width for Upcoming Interviews -->
            <div>
                <!-- Upcoming Interviews Card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Upcoming Interviews</h3>
                        <button class="text-sm text-[#0078C8] hover:text-[#0066B3] font-medium">
                            <a href="{{ route('hrm.candidates.calendar.data') }}">View All Schedule</a>
                        </button>
                    </div>

                    <div class="space-y-4">
                        @forelse($upcomingInterviews as $interview)
                            <div class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                <div class="flex items-center">
                                    <div
                                        class="w-10 h-10 {{ $interview['color_class'] == 'blue' ? 'bg-blue-100' : ($interview['color_class'] == 'green' ? 'bg-green-100' : 'bg-purple-100') }} rounded-full flex items-center justify-center mr-3">
                                        <span
                                            class="text-sm font-medium {{ $interview['color_class'] == 'blue' ? 'text-blue-600' : ($interview['color_class'] == 'green' ? 'text-green-600' : 'text-purple-600') }}">
                                            {{ $interview['initials'] }}
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900">{{ $interview['full_name'] }}</h4>
                                        <p class="text-sm text-gray-500">{{ $interview['current_role'] }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900">{{ $interview['interview_time'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $interview['interview_date_formatted'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <p class="text-gray-500">No upcoming interviews</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Applications Card (Full Width Below) -->
        <div class="mt-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Applications</h3>
                    <button class="text-sm text-[#0078C8] hover:text-[#0066B3] font-medium">
                        <a href="{{ route('hrm.candidates.index') }}">View All</a>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 text-sm font-medium text-gray-600 uppercase tracking-wider">
                                    CANDIDATE</th>
                                <th class="text-left py-3 text-sm font-medium text-gray-600 uppercase tracking-wider">ROLE
                                    APPLIED</th>
                                <th class="text-left py-3 text-sm font-medium text-gray-600 uppercase tracking-wider">
                                    APPLIED DATE</th>
                                <th class="text-left py-3 text-sm font-medium text-gray-600 uppercase tracking-wider">STAGE
                                </th>
                                <th class="text-left py-3 text-sm font-medium text-gray-600 uppercase tracking-wider">
                                    ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentApplications as $application)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-4">
                                        <div class="flex items-center">
                                            <div
                                                class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                <span
                                                    class="text-sm font-medium text-blue-600">{{ $application['initials'] }}</span>
                                            </div>
                                            <span class="font-medium text-gray-900">{{ $application['full_name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 text-gray-700">{{ $application['role_applied'] }}</td>
                                    <td class="py-4 text-gray-700">{{ $application['applied_date'] }}</td>
                                    <td class="py-4">
                                        @php
                                            $stageColors = [
                                                'Applied' => 'bg-blue-50 text-blue-600',
                                                'New' => 'bg-blue-50 text-blue-600',
                                                'Screening' => 'bg-purple-50 text-purple-600',
                                                'Interview' => 'bg-green-50 text-green-600',
                                                'Offer' => 'bg-amber-50 text-amber-600',
                                                'Hired' => 'bg-green-50 text-green-600',
                                                'Rejected' => 'bg-red-50 text-red-600',
                                            ];
                                            $stageColor =
                                                $stageColors[$application['stage']] ?? 'bg-gray-50 text-gray-600';
                                        @endphp
                                        <span class="px-3 py-1 {{ $stageColor }} text-xs font-medium rounded-full">
                                            {{ $application['stage'] }}
                                        </span>
                                    </td>
                                    <td class="py-4">
                                        <a href="{{ route('hrm.candidates.show', $application['id']) }}"
                                            class="text-[#0078C8] hover:text-[#0066B3] text-sm font-medium">
                                            Review
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-gray-500">
                                        No recent applications
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Stats Card (Full Width Below Recent Applications) -->
        <div class="mt-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Recruitment Quick Stats</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Offers Accepted -->
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-600">Offers Accepted</p>
                                <p class="text-xl font-bold text-gray-900">{{ $quickStats['offer_acceptance_rate'] }}%</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quality Score -->
                    <div class="p-3 bg-green-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-600">Quality Score</p>
                                <p class="text-xl font-bold text-gray-900">{{ $quickStats['quality_score'] }}/5.0</p>
                            </div>
                        </div>
                    </div>

                    <!-- Hiring Satisfaction -->
                    <div class="p-3 bg-amber-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-600">Hiring Satisfaction</p>
                                <p class="text-xl font-bold text-gray-900">{{ $quickStats['hiring_satisfaction'] }}%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-refresh dashboard data every 30 seconds
            setInterval(refreshDashboardMetrics, 30000);

            // Function to refresh dashboard metrics via AJAX
            function refreshDashboardMetrics() {
                fetch('{{ route('hrm.recruitment.metrics') }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateDashboardMetrics(data.data);
                        }
                    })
                    .catch(error => console.error('Error refreshing metrics:', error));
            }

            // Function to update UI with new metrics
            function updateDashboardMetrics(data) {
                // Update metrics cards
                const activeJobsElement = document.querySelector('.bg-white:first-child .text-3xl');
                const newApplicationsElement = document.querySelector('.bg-white:nth-child(2) .text-3xl');
                const interviewsTodayElement = document.querySelector('.bg-white:nth-child(3) .text-3xl');

                if (activeJobsElement) activeJobsElement.textContent = data.metrics.active_jobs;
                if (newApplicationsElement) newApplicationsElement.textContent = data.metrics.new_applications;
                if (interviewsTodayElement) interviewsTodayElement.textContent = data.metrics.interviews_today;

                // Update pipeline stats
                const pipelineData = data.pipeline;
                updatePipelineStats(pipelineData);
            }

            // Function to update pipeline stats
            function updatePipelineStats(pipelineData) {
                const stages = ['applied', 'screening', 'interview', 'offer', 'hired'];

                stages.forEach(stage => {
                    const countElement = document.querySelector(`[data-stage="${stage}"] .candidate-count`);
                    const barElement = document.querySelector(`[data-stage="${stage}"] .progress-bar`);

                    if (countElement && pipelineData[stage]) {
                        countElement.textContent = `${pipelineData[stage].count} Candidates`;
                    }

                    if (barElement && pipelineData[stage]) {
                        barElement.style.width = `${pipelineData[stage].percentage}%`;
                    }
                });
            }

            // Initialize dashboard with live data
            refreshDashboardMetrics();
        });
    </script>
@endpush
