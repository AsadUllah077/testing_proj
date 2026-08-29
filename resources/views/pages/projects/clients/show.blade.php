@extends('layouts.auth')
@section('title', $client->name)

@section('content')
    <div class="mt-4 mx-auto px-3">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('project.clients.index') }}" class="hover:text-[#0078C8]">Clients</a>
            <span>/</span>
            <span class="text-gray-800">{{ $client->name }}</span>
        </div>

        <!-- Header Card -->
        <div class="bg-white rounded-xl border border-gray-200 mb-5">
            <div class="p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <!-- Avatar -->
                        <div class="w-14 h-14 bg-gradient-to-br from-[#0078C8] to-[#005a96] rounded-xl flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
                            {{ strtoupper(mb_substr($client->name, 0, 2)) }}
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900">{{ $client->name }}</h1>
                            <div class="flex flex-wrap items-center gap-3 mt-1.5 text-sm text-gray-500">
                                @if($client->industry)
                                <span class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                                    </svg>
                                    {{ $client->industry }}
                                </span>
                                @endif
                                @if($client->email)
                                <span class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                    {{ $client->email }}
                                </span>
                                @endif
                                @if($client->phone)
                                <span class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25z" />
                                    </svg>
                                    {{ $client->phone }}
                                </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $client->type === 'company' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ ucfirst($client->type) }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $client->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ ucfirst($client->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if(auth()->user()->hasPermission('project.clients.edit'))
                        <a href="{{ route('project.clients.edit', $client) }}"
                            class="px-4 py-2 bg-[#0078C8] text-white text-xs rounded-lg hover:bg-[#006bb0] flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                            </svg>
                            Edit Client
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-5">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-2xl font-bold text-gray-800">{{ $client->projects->count() }}</div>
                <div class="text-xs text-gray-500 mt-1">Total Projects</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $client->projects->where('status', 'completed')->count() }}</div>
                <div class="text-xs text-gray-500 mt-1">Completed</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $client->projects->where('status', 'active')->count() }}</div>
                <div class="text-xs text-gray-500 mt-1">Active</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Projects List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Projects ({{ $client->projects->count() }})</h3>
                        @if(auth()->user()->hasPermission('project.projects.create'))
                        <a href="{{ route('project.projects.create') }}"
                            class="px-3 py-1.5 bg-[#0078C8] text-white text-xs rounded-lg hover:bg-[#006bb0] flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            New Project
                        </a>
                        @endif
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse($client->projects as $project)
                        <div class="px-5 py-3.5 flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="{{ route('project.projects.show', $project) }}"
                                        class="text-sm font-medium text-[#0078C8] hover:underline truncate">{{ $project->name }}</a>
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $project->priority_color }}">{{ ucfirst($project->priority) }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $project->status_color }}">{{ ucfirst(str_replace('-', ' ', $project->status)) }}</span>
                                </div>
                                <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                                    @if($project->manager)
                                        <span>{{ $project->manager->full_name }}</span>
                                    @endif
                                    @if($project->end_date)
                                        <span>Due {{ $project->end_date->format('d M Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <div class="text-xs text-gray-500 w-8 text-right">{{ $project->progress }}%</div>
                                <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-[#0078C8] h-1.5 rounded-full" style="width: {{ $project->progress }}%"></div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="px-5 py-10 text-center text-gray-400 text-sm">
                            No projects yet.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right Column: Details -->
            <div class="space-y-5">
                <!-- Contact Info -->
                @if($client->contact_person || $client->contact_email || $client->contact_phone)
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Primary Contact</h3>
                    <div class="space-y-2.5 text-sm">
                        @if($client->contact_person)
                        <div class="flex items-center gap-2 text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400 flex-shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            {{ $client->contact_person }}
                        </div>
                        @endif
                        @if($client->contact_email)
                        <div class="flex items-center gap-2 text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400 flex-shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            {{ $client->contact_email }}
                        </div>
                        @endif
                        @if($client->contact_phone)
                        <div class="flex items-center gap-2 text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400 flex-shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25z" />
                            </svg>
                            {{ $client->contact_phone }}
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Address -->
                @if($client->address || $client->city || $client->country)
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Address</h3>
                    <div class="text-sm text-gray-600 space-y-0.5">
                        @if($client->address)<div>{{ $client->address }}</div>@endif
                        @if($client->city || $client->state)
                            <div>{{ collect([$client->city, $client->state])->filter()->implode(', ') }}</div>
                        @endif
                        @if($client->country || $client->postal_code)
                            <div>{{ collect([$client->country, $client->postal_code])->filter()->implode(' ') }}</div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Website -->
                @if($client->website)
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Website</h3>
                    <a href="{{ $client->website }}" target="_blank" rel="noopener"
                        class="text-sm text-[#0078C8] hover:underline break-all">{{ $client->website }}</a>
                </div>
                @endif

                <!-- Notes -->
                @if($client->notes)
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Notes</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $client->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection
