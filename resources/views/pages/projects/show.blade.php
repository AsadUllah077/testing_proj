@extends('layouts.auth')
@section('title', $project->name)

@section('content')
    <div class="mt-4 mx-auto px-3">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('project.projects.index') }}" class="hover:text-[#0078C8]">Projects</a>
            <span>/</span>
            <span class="text-gray-800">{{ $project->name }}</span>
        </div>

        <!-- Project Header -->
        <div class="bg-white rounded-xl border border-gray-200  mb-5">
            <div class="p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <!-- Progress Circle -->
                        <div class="relative w-16 h-16 flex-shrink-0">
                            <svg class="w-16 h-16 -rotate-90" viewBox="0 0 64 64">
                                <circle cx="32" cy="32" r="26" fill="none" stroke="#e5e7eb" stroke-width="6"/>
                                <circle cx="32" cy="32" r="26" fill="none" stroke="#0078C8" stroke-width="6"
                                    stroke-dasharray="{{ round(2 * M_PI * 26, 1) }}"
                                    stroke-dashoffset="{{ round(2 * M_PI * 26 * (1 - $project->progress / 100), 1) }}"
                                    stroke-linecap="round"/>
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-xs font-semibold text-gray-800">
                                {{ $project->progress }}%
                            </span>
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900">{{ $project->name }}</h1>
                            <div class="flex flex-wrap items-center gap-3 mt-1.5 text-sm text-gray-500">
                                @if($project->client)
                                <span class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                                    </svg>
                                    {{ $project->client }}
                                </span>
                                @endif
                                @if($project->manager)
                                <span class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                    {{ $project->manager->full_name }}
                                </span>
                                @endif
                                @if($project->start_date)
                                <span class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                                    </svg>
                                    {{ $project->start_date->format('d M Y') }}
                                    @if($project->end_date) — {{ $project->end_date->format('d M Y') }} @endif
                                </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $project->status_color }}">
                                    {{ ucfirst(str_replace('-', ' ', $project->status)) }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $project->priority_color }}">
                                    {{ ucfirst($project->priority) }} Priority
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if(auth()->user()->hasPermission('project.projects.edit'))
                        <a href="{{ route('project.projects.edit', $project) }}"
                            class="px-4 py-2 bg-[#0078C8] text-white text-xs rounded-lg hover:bg-[#006bb0] flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                            </svg>
                            Edit Project
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-2xl font-bold text-gray-800">{{ $totalTasks }}</div>
                <div class="text-xs text-gray-500 mt-1">Total Tasks</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $completedTasks }}</div>
                <div class="text-xs text-gray-500 mt-1">Completed</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $inProgressTasks }}</div>
                <div class="text-xs text-gray-500 mt-1">In Progress</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                @if($project->teams->isNotEmpty())
                    <div class="text-lg font-bold text-[#0078C8] truncate">{{ $project->teams->first()->name }}</div>
                @else
                    <div class="text-lg font-bold text-gray-300">—</div>
                @endif
                <div class="text-xs text-gray-500 mt-1">Team</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Tasks Section -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-gray-200  overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Tasks</h3>
                        @if(auth()->user()->hasPermission('project.projects.tasks.create'))
                        <button onclick="openAddTaskModal()"
                            class="px-3 py-1.5 bg-[#0078C8] text-white text-xs rounded-lg hover:bg-[#006bb0] flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add Task
                        </button>
                        @endif
                    </div>
                    <div class="divide-y divide-gray-100" id="tasks-list">
                        @forelse($project->tasks as $task)
                        <div class="px-5 py-3.5 flex items-center gap-3 group" id="task-row-{{ $task->id }}">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-medium text-gray-800 truncate">{{ $task->title }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $task->priority_color }}">{{ ucfirst($task->priority) }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $task->status_color }}">{{ ucfirst(str_replace('-', ' ', $task->status)) }}</span>
                                </div>
                                <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                                    @if($task->assignee)
                                        <span>{{ $task->assignee->full_name }}</span>
                                    @endif
                                    @if($task->due_date)
                                        <span>Due {{ $task->due_date->format('d M Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            <!-- Action buttons (visible on hover) -->
                            <div class="opacity-0 group-hover:opacity-100 flex items-center gap-1 transition-all flex-shrink-0">
                                <button onclick="openViewTaskModal({{ json_encode($task->title) }}, {{ json_encode($task->assignee?->full_name) }}, {{ json_encode($task->due_date?->format('d M Y')) }}, {{ json_encode($task->priority) }}, {{ json_encode($task->status) }}, {{ json_encode($task->description) }}, {{ json_encode($task->created_at->format('d M Y')) }})"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-all"
                                    title="View detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </button>
                                @if(auth()->user()->hasPermission('project.projects.tasks.edit'))
                                <button onclick="openEditTaskModal({{ $task->id }}, {{ json_encode($task->title) }}, {{ json_encode($task->assignee_id) }}, {{ json_encode($task->due_date?->format('Y-m-d')) }}, {{ json_encode($task->priority) }}, {{ json_encode($task->status) }}, {{ json_encode($task->description) }})"
                                    class="p-1.5 text-blue-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-all"
                                    title="Edit task">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                    </svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('project.projects.tasks.delete'))
                                <button onclick="deleteTask({{ $task->id }})"
                                    class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-all"
                                    title="Delete task">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div id="no-tasks-msg" class="px-5 py-10 text-center text-gray-400 text-sm">
                            No tasks yet.
                            @if(auth()->user()->hasPermission('project.projects.tasks.create'))
                                <button onclick="openAddTaskModal()" class="text-[#0078C8] hover:underline ml-1">Add the first task</button>
                            @endif
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right Column: Info + Team -->
            <div class="space-y-5">
                @if($project->description)
                <div class="bg-white rounded-xl border border-gray-200  p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">About</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $project->description }}</p>
                </div>
                @endif

                @if($project->budget)
                <div class="bg-white rounded-xl border border-gray-200  p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Budget</h3>
                    <div class="text-2xl font-bold text-gray-900">${{ number_format($project->budget, 0) }}</div>
                    <div class="text-xs text-gray-400 mt-1">Allocated Budget</div>
                </div>
                @endif

                @if($project->members->isNotEmpty())
                <div class="bg-white rounded-xl border border-gray-200  p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Team ({{ $project->members->count() }})</h3>
                    <div class="space-y-2.5">
                        @foreach($project->members as $member)
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-[#0078C8] to-[#005a96] rounded-full flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                                {{ strtoupper(mb_substr($member->full_name ?? '', 0, 2)) }}
                            </div>
                            <div class="text-sm font-medium text-gray-800">{{ $member->full_name }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @foreach($project->teams as $team)
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-[#0078C8]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                            {{ $team->name }}
                        </h3>
                        <span class="text-xs text-gray-400">{{ $team->members->count() }} {{ Str::plural('member', $team->members->count()) }}</span>
                    </div>
                    @if($team->members->isNotEmpty())
                        <div class="space-y-2.5">
                            @foreach($team->members as $member)
                                @php
                                    $colors = ['from-blue-500 to-blue-600','from-green-500 to-green-600','from-purple-500 to-purple-600','from-orange-500 to-orange-600','from-pink-500 to-pink-600','from-teal-500 to-teal-600'];
                                    $color = $colors[$loop->index % count($colors)];
                                @endphp
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gradient-to-br {{ $color }} rounded-full flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                                        {{ strtoupper(mb_substr($member->full_name ?? '', 0, 2)) }}
                                    </div>
                                    <div class="text-sm font-medium text-gray-800">{{ $member->full_name }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400">No members assigned to this team.</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===== ADD TASK MODAL ===== --}}
    @if(auth()->user()->hasPermission('project.projects.tasks.create'))
    <div id="addTaskModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Add Task</h3>
                <button onclick="closeAddTaskModal()" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="addTaskForm" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Task Title <span class="text-red-500">*</span></label>
                    <input type="text" id="add-task-title" required placeholder="Enter task title"
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Assignee</label>
                        <select id="add-task-assignee" class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                            <option value="">Unassigned</option>
                            @foreach($project->members as $member)
                                <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Due Date</label>
                        <input type="date" id="add-task-due-date"
                            class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Priority</label>
                        <select id="add-task-priority" class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                        <select id="add-task-status" class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                            <option value="todo" selected>To Do</option>
                            <option value="in-progress">In Progress</option>
                            <option value="review">Review</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                    <textarea id="add-task-description" rows="2" placeholder="Optional description..."
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none resize-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" onclick="closeAddTaskModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-[#0078C8] text-white text-sm rounded-lg hover:bg-[#006bb0]">Add Task</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ===== VIEW TASK MODAL ===== --}}
    <div id="viewTaskModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Task Detail</h3>
                <button onclick="closeViewTaskModal()" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Title</div>
                    <div id="view-task-title" class="text-sm font-semibold text-gray-900"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Status</div>
                        <div id="view-task-status-badge"></div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Priority</div>
                        <div id="view-task-priority-badge"></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Assignee</div>
                        <div id="view-task-assignee" class="text-sm text-gray-700">—</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Due Date</div>
                        <div id="view-task-due-date" class="text-sm text-gray-700">—</div>
                    </div>
                </div>
                <div id="view-task-description-wrap">
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Description</div>
                    <div id="view-task-description" class="text-sm text-gray-600 leading-relaxed bg-gray-50 rounded-lg px-3 py-2.5"></div>
                </div>
                <div>
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Created</div>
                    <div id="view-task-created-at" class="text-sm text-gray-500"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== EDIT TASK MODAL ===== --}}
    @if(auth()->user()->hasPermission('project.projects.tasks.edit'))
    <div id="editTaskModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Edit Task</h3>
                <button onclick="closeEditTaskModal()" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="editTaskForm" class="p-6 space-y-4">
                @csrf
                <input type="hidden" id="edit-task-id">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Task Title <span class="text-red-500">*</span></label>
                    <input type="text" id="edit-task-title" required placeholder="Enter task title"
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Assignee</label>
                        <select id="edit-task-assignee" class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                            <option value="">Unassigned</option>
                            @foreach($project->members as $member)
                                <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Due Date</label>
                        <input type="date" id="edit-task-due-date"
                            class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none date">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Priority</label>
                        <select id="edit-task-priority" class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                        <select id="edit-task-status" class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                            <option value="todo">To Do</option>
                            <option value="in-progress">In Progress</option>
                            <option value="review">Review</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                    <textarea id="edit-task-description" rows="2" placeholder="Optional description..."
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none resize-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" onclick="closeEditTaskModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-[#0078C8] text-white text-sm rounded-lg hover:bg-[#006bb0]">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
    const csrf   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const baseTaskUrl = '{{ url('projects/' . $project->id . '/tasks') }}';

    // ---- VIEW TASK ----
    const viewTaskModal = document.getElementById('viewTaskModal');

    const priorityColors = {
        low:      'bg-green-100 text-green-700',
        medium:   'bg-yellow-100 text-yellow-700',
        high:     'bg-orange-100 text-orange-700',
        critical: 'bg-red-100 text-red-700',
    };
    const statusColors = {
        'todo':        'bg-gray-100 text-gray-700',
        'in-progress': 'bg-blue-100 text-blue-700',
        'review':      'bg-purple-100 text-purple-700',
        'completed':   'bg-green-100 text-green-700',
    };

    function openViewTaskModal(title, assignee, dueDate, priority, status, description, createdAt) {
        document.getElementById('view-task-title').textContent = title ?? '—';

        const statusLabel = status ? status.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '—';
        const priorityLabel = priority ? priority.charAt(0).toUpperCase() + priority.slice(1) : '—';

        document.getElementById('view-task-status-badge').innerHTML =
            `<span class="px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColors[status] ?? 'bg-gray-100 text-gray-700'}">${statusLabel}</span>`;
        document.getElementById('view-task-priority-badge').innerHTML =
            `<span class="px-2.5 py-0.5 rounded-full text-xs font-medium ${priorityColors[priority] ?? 'bg-gray-100 text-gray-700'}">${priorityLabel}</span>`;

        document.getElementById('view-task-assignee').textContent  = assignee  ?? '—';
        document.getElementById('view-task-due-date').textContent   = dueDate   ?? '—';
        document.getElementById('view-task-created-at').textContent = createdAt ?? '—';

        const descWrap = document.getElementById('view-task-description-wrap');
        const descEl   = document.getElementById('view-task-description');
        if (description) {
            descEl.textContent = description;
            descWrap.classList.remove('hidden');
        } else {
            descWrap.classList.add('hidden');
        }

        viewTaskModal?.classList.remove('hidden');
    }
    function closeViewTaskModal() { viewTaskModal?.classList.add('hidden'); }
    window.openViewTaskModal  = openViewTaskModal;
    window.closeViewTaskModal = closeViewTaskModal;

    // ---- ADD TASK ----
    const addTaskModal = document.getElementById('addTaskModal');
    function openAddTaskModal() {
        document.getElementById('addTaskForm')?.reset();
        addTaskModal?.classList.remove('hidden');
    }
    function closeAddTaskModal() { addTaskModal?.classList.add('hidden'); }
    window.openAddTaskModal  = openAddTaskModal;
    window.closeAddTaskModal = closeAddTaskModal;

    document.getElementById('addTaskForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = {
            title:       document.getElementById('add-task-title').value,
            assignee_id: document.getElementById('add-task-assignee').value || null,
            due_date:    document.getElementById('add-task-due-date').value || null,
            priority:    document.getElementById('add-task-priority').value,
            status:      document.getElementById('add-task-status').value,
            description: document.getElementById('add-task-description').value || null,
        };
        try {
            const res  = await fetch(baseTaskUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(body)
            });
            const data = await res.json();
            if (res.ok && data.success) {
                Notification.success(data.message);
                closeAddTaskModal();
                window.location.reload();
            } else {
                Notification.error(data.message || 'Failed to add task.');
            }
        } catch (err) {
            Notification.error('Network error.');
        }
    });

    // ---- EDIT TASK ----
    const editTaskModal = document.getElementById('editTaskModal');

    function openEditTaskModal(id, title, assigneeId, dueDate, priority, status, description) {
        document.getElementById('edit-task-id').value          = id;
        document.getElementById('edit-task-title').value       = title ?? '';
        document.getElementById('edit-task-assignee').value    = assigneeId ?? '';
        document.getElementById('edit-task-due-date').value    = dueDate ?? '';
        document.getElementById('edit-task-priority').value    = priority ?? 'medium';
        document.getElementById('edit-task-status').value      = status ?? 'todo';
        document.getElementById('edit-task-description').value = description ?? '';
        editTaskModal?.classList.remove('hidden');
    }
    function closeEditTaskModal() { editTaskModal?.classList.add('hidden'); }
    window.openEditTaskModal  = openEditTaskModal;
    window.closeEditTaskModal = closeEditTaskModal;

    document.getElementById('editTaskForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const taskId = document.getElementById('edit-task-id').value;
        const body = {
            title:       document.getElementById('edit-task-title').value,
            assignee_id: document.getElementById('edit-task-assignee').value || null,
            due_date:    document.getElementById('edit-task-due-date').value || null,
            priority:    document.getElementById('edit-task-priority').value,
            status:      document.getElementById('edit-task-status').value,
            description: document.getElementById('edit-task-description').value || null,
        };
        try {
            const res  = await fetch(`${baseTaskUrl}/${taskId}`, {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(body)
            });
            const data = await res.json();
            if (res.ok && data.success) {
                Notification.success(data.message);
                closeEditTaskModal();
                window.location.reload();
            } else {
                Notification.error(data.message || 'Failed to update task.');
            }
        } catch (err) {
            Notification.error('Network error.');
        }
    });

    // ---- DELETE TASK ----
    async function deleteTask(taskId) {
        if (!confirm('Delete this task?')) return;
        try {
            const res  = await fetch(`${baseTaskUrl}/${taskId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                document.getElementById(`task-row-${taskId}`)?.remove();
                Notification.success(data.message);
            } else {
                Notification.error(data.message || 'Failed to delete task.');
            }
        } catch (err) {
            Notification.error('Network error.');
        }
    }
    window.deleteTask = deleteTask;

    // Close modals on backdrop click
    [addTaskModal, editTaskModal, viewTaskModal].forEach(modal => {
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        });
    });
</script>
@endpush
