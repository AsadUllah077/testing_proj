@extends('layouts.auth')
@section('title', 'Create Project')

@section('content')
    <x-acccount.loader />
    <div class="mt-4 mx-auto px-3 max-w-12xl">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('project.projects.index') }}" class="hover:text-[#0078C8]">Projects</a>
            <span>/</span>
            <span class="text-gray-800">Create New Project</span>
        </div>

        <div class="bg-white rounded-xl border border-gray-200  overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Create New Project</h2>
            </div>

            <form id="formSubmit" method="post"
                  action_url="{{ route('project.projects.store') }}"
                  back_url="{{ route('project.projects.index') }}"
                  class="p-6 space-y-6">
                @csrf

                <!-- Section: Basic Information -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-[#0078C8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        Basic Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Project Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                placeholder="Enter project name"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <div id="name_error" class="text-red-500 text-xs mt-1"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Client</label>
                            <select name="client_id"
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">Select client</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="client_id_error" class="text-red-500 text-xs mt-1"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Project Manager</label>
                            <select name="manager_id"
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">Select manager</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('manager_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->full_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="manager_id_error" class="text-red-500 text-xs mt-1"></div>
                        </div>
                    </div>
                </div>

                <!-- Section: Timeline & Priority -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-[#0078C8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                        </svg>
                        Timeline & Priority
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Start Date</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}"
                                class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <div id="start_date_error" class="text-red-500 text-xs mt-1"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">End Date</label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}"
                                class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <div id="end_date_error" class="text-red-500 text-xs mt-1"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Priority Level <span class="text-red-500">*</span></label>
                            <select name="priority" required
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ old('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                            <div id="priority_error" class="text-red-500 text-xs mt-1"></div>
                        </div>
                    </div>
                </div>

                <!-- Section: Budget & Team -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-[#0078C8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                        Budget & Team
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Budget Allocation ($)</label>
                            <input type="number" name="budget" value="{{ old('budget') }}" min="0" step="0.01"
                                placeholder="0.00"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <div id="budget_error" class="text-red-500 text-xs mt-1"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Status <span class="text-red-500">*</span></label>
                            <select name="status" required
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="planning" {{ old('status', 'planning') === 'planning' ? 'selected' : '' }}>Planning</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="on-hold" {{ old('status') === 'on-hold' ? 'selected' : '' }}>On Hold</option>
                                <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                            <div id="status_error" class="text-red-500 text-xs mt-1"></div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Assign Team</label>
                            <select name="team_id"
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">— No Team —</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}" {{ old('team_id') == $team->id ? 'selected' : '' }}>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section: Description -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-[#0078C8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        Project Description
                    </h3>
                    <textarea name="description" rows="4" placeholder="Describe the project scope, goals, and objectives..."
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all resize-none">{{ old('description') }}</textarea>
                    <div id="description_error" class="text-red-500 text-xs mt-1"></div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-2 border-t border-gray-100">
                    <a href="{{ route('project.projects.index') }}"
                        class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors text-center">
                        Cancel
                    </a>
                    <button type="submit" id="submitBtn"
                        class="px-5 py-2.5 bg-[#0078C8] text-white text-sm rounded-lg hover:bg-[#006bb0] transition-colors text-center">
                        Create Project
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
