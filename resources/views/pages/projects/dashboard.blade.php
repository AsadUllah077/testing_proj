@extends('layouts.auth')
@section('title', 'Project Dashboard')

@section('content')
    <div class="mt-4 mx-auto px-3">
        <!-- Header -->
        <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
            <div>
                <h2 class="text-stone-950 text-xl font-normal">Project Dashboard</h2>
                <p class="text-sm text-gray-500 mt-0.5">Overview of all projects and progress</p>
            </div>
            @if(auth()->user()->hasPermission('project.projects.create'))
            <a href="{{ route('project.projects.create') }}"
                class="inline-flex px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] items-center gap-2 w-full sm:w-auto justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                NEW PROJECT
            </a>
            @endif
        </div>

        <!-- Stats Cards -->
        @php
            $allProjects    = \App\Models\Project::where('company_id', auth()->user()->company_id);
            $totalProjects  = (clone $allProjects)->count();
            $activeCount    = (clone $allProjects)->where('status', 'active')->count();
            $completedCount = (clone $allProjects)->where('status', 'completed')->count();
            $totalTasks     = \App\Models\ProjectTask::whereHas('project', fn($q) => $q->where('company_id', auth()->user()->company_id))->count();
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="p-2 bg-blue-50 rounded-lg w-fit mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-[#0078C8]">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                </div>
                <div class="text-2xl font-bold text-gray-900">{{ $totalProjects }}</div>
                <div class="text-xs text-gray-500 mt-1">Total Projects</div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="p-2 bg-green-50 rounded-lg w-fit mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                    </svg>
                </div>
                <div class="text-2xl font-bold text-gray-900">{{ $activeCount }}</div>
                <div class="text-xs text-gray-500 mt-1">Active Projects</div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="p-2 bg-gray-50 rounded-lg w-fit mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="text-2xl font-bold text-gray-900">{{ $completedCount }}</div>
                <div class="text-xs text-gray-500 mt-1">Completed</div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="p-2 bg-orange-50 rounded-lg w-fit mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-orange-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
                <div class="text-2xl font-bold text-gray-900">{{ $totalTasks }}</div>
                <div class="text-xs text-gray-500 mt-1">Total Tasks</div>
            </div>
        </div>

        <!-- Recent Projects -->
        <div class="bg-white rounded-xl border border-gray-200  overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Recent Projects</h3>
                @if(auth()->user()->hasPermission('project.projects.view'))
                <a href="{{ route('project.projects.index') }}" class="text-xs text-[#0078C8] hover:underline">View all</a>
                @endif
            </div>
            @php
                $recentProjects = \App\Models\Project::with(['manager', 'tasks'])
                    ->where('company_id', auth()->user()->company_id)
                    ->latest()
                    ->limit(8)
                    ->get();
            @endphp
            @if($recentProjects->isEmpty())
            <div class="px-5 py-12 text-center text-gray-400 text-sm">
                No projects yet.
                @if(auth()->user()->hasPermission('project.projects.create'))
                    <a href="{{ route('project.projects.create') }}" class="text-[#0078C8] hover:underline ml-1">Create your first project</a>
                @endif
            </div>
            @else
            <div class="divide-y divide-gray-100">
                @foreach($recentProjects as $project)
                <div class="px-5 py-4 flex items-center gap-4 hover:bg-gray-50 transition-colors">
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('project.projects.show', $project) }}"
                            class="font-medium text-gray-900 hover:text-[#0078C8] truncate block">{{ $project->name }}</a>
                        <div class="flex items-center gap-3 mt-1 text-xs text-gray-400 flex-wrap">
                            @if($project->client)<span>{{ $project->client }}</span>@endif
                            @if($project->manager)<span>{{ $project->manager->full_name }}</span>@endif
                            @if($project->end_date)<span>Due {{ $project->end_date->format('d M Y') }}</span>@endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="px-2 py-0.5 rounded-full text-xs {{ $project->status_color }}">
                            {{ ucfirst(str_replace('-', ' ', $project->status)) }}
                        </span>
                        <div class="flex items-center gap-1.5 min-w-[80px]">
                            <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                                <div class="bg-[#0078C8] h-1.5 rounded-full" style="width: {{ $project->progress }}%"></div>
                            </div>
                            <span class="text-xs text-gray-400 w-8 text-right">{{ $project->progress }}%</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
@endsection
