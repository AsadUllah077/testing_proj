@extends('layouts.auth')

@section('title', 'Announcements')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3 max-w-4xl">
            <div class="overflow-hidden">
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal">Announcements</h2>
                        <p class="text-gray-600 text-sm mt-1">Company-wide announcements</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('hrm.announcements.create') }}"
                            class="px-4 py-2 bg-[#0078C8] text-white rounded hover:bg-[#006bb0] text-xs font-normal transition-colors">
                            Add Announcement
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200">
                    <!-- Search & Filter Bar -->
                    <div class="p-4 border-b border-gray-200">
                        <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-2">
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search title"
                                class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none" />
                            <select name="category"
                                class="select2 w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">
                                <option value="">All categories</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}"
                                        {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                            <select name="priority"
                                class="select2 w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">
                                <option value="">All priorities</option>
                                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium
                                </option>
                                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                            </select>
                            <button
                                class="px-4 py-2 bg-[#0078C8] text-white rounded text-sm hover:bg-[#006bb0] transition-colors">Apply</button>
                        </form>
                    </div>

                    <!-- Announcements Table -->
                    <div class="p-4">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Title</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Category</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Priority</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($announcements as $a)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                <a href="{{ route('hrm.announcements.show', $a->id) }}"
                                                    class="hover:underline text-[#0078C8]">{{ $a->title }}</a>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $a->category ?? '—' }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <span
                                                    class="px-2 py-1 text-xs rounded-full
                                                    {{ $a->priority === 'high' ? 'bg-red-100 text-red-700' : ($a->priority === 'low' ? 'bg-gray-100 text-gray-700' : 'bg-yellow-100 text-yellow-700') }}">
                                                    {{ ucfirst($a->priority) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right">
                                                <div class="inline-flex items-center gap-2">
                                                    <a href="{{ route('hrm.announcements.download-pdf', $a->id) }}"
                                                        class="text-green-600 hover:underline text-xs">PDF</a>
                                                    <a href="{{ route('hrm.announcements.edit', $a->id) }}"
                                                        class="text-[#0078C8] hover:underline text-xs">Edit</a>
                                                    <form action="{{ route('hrm.announcements.destroy', $a->id) }}"
                                                        method="post" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button onclick="return confirm('Are you sure?')"
                                                            class="text-red-600 hover:underline text-xs">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-600">No
                                                announcements found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <x-data-table-paginatior :paginator="$announcements" />
                    </div>
                </div>
            </div>
        </div>
    @endsection
