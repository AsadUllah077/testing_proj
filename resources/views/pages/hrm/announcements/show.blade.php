@extends('layouts.auth')

@section('title', 'Announcement Details')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3 max-w-4xl">
            <div class="overflow-hidden">
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal">Announcement Details</h2>
                        <p class="text-gray-600 text-sm mt-1">View announcement information</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('hrm.announcements.download-pdf', $announcement->id) }}"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-xs font-normal transition-colors">Download
                            PDF</a>
                        <a href="{{ route('hrm.announcements.edit', $announcement->id) }}"
                            class="px-4 py-2 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal transition-colors">Edit</a>
                        <a href="{{ route('hrm.announcements.index') }}"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-xs font-normal">Back</a>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Title</label>
                            <p class="text-lg font-semibold text-gray-900">{{ $announcement->title }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Category</label>
                            <p class="text-lg font-semibold text-gray-900">{{ $announcement->category ?? '—' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Priority</label>
                            <span
                                class="px-2 py-1 text-xs rounded-full
                                {{ $announcement->priority === 'high' ? 'bg-red-100 text-red-700' : ($announcement->priority === 'low' ? 'bg-gray-100 text-gray-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ ucfirst($announcement->priority) }}
                            </span>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-600 mb-1">Content</label>
                            <div class="prose prose-sm max-w-none">
                                <p class="text-gray-800 whitespace-pre-line">{{ $announcement->content }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
