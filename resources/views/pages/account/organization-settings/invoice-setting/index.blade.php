@extends('layouts.auth')

@section('title', 'Invoice Templates')

@section('content')
    <div class="mb-8">
        <div class="p-3">
            <div class="mx-auto">
                <div class="p-5 bg-white rounded-lg border border-black/10">

                    <!-- Breadcrumbs -->
                    <nav class="flex mb-4" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-[#0078C8]">
                                <a href="{{ route('account.organization-settings.index') }}" class="flex items-center">
                                    <span class="material-symbols-outlined text-base mr-1">settings</span>
                                    Settings
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center text-gray-400">
                                    <span class="material-symbols-outlined text-base">chevron_right</span>
                                    <span class="ml-1 text-sm font-semibold text-gray-900">Invoice Templates</span>
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <!-- Header Row -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                        <!-- Title -->
                        <div>
                            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                                Invoice Templates
                            </h1>
                            <p class="text-sm text-gray-500 font-medium">
                                Manage your invoice templates.
                            </p>
                        </div>

                        <!-- Button -->
                        <a href="{{ route('account.organization-settings.invoice-templates.create') }}"
                            class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded hover:bg-[#006bb0] transition-all">
                            Create New Template
                        </a>

                    </div>
                </div>

                <div class="mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @forelse($templates as $template)
                            <div
                                class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                <!-- Template Preview Header -->
                                <div class="p-4 border-gray-200">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $template->name }}</h3>
                                        @if ($template->is_default)
                                            <span
                                                class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">Default</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium">Page:</span> {{ $template->page_size }} |
                                        <span class="font-medium">Font:</span> {{ $template->font ?? 'Arial' }}
                                    </div>
                                </div>

                                <!-- Template Preview Body -->
                                <div class="p-4">
                                    <div class="bg-gray-50 border rounded p-3 mb-4" style="min-height: 120px;">
                                        <!-- Mock Invoice Preview -->
                                        <div class="text-xs text-gray-700 space-y-1">
                                            @if ($template->show_logo && $template->logo)
                                                <div class="text-center mb-2">
                                                    <div
                                                        class="w-50 h-50 bg-gray-300 rounded mx-auto flex items-center justify-center">
                                                        <span class="text-xs">
                                                            <img src="{{ asset('storage/' . $template->logo) }}"
                                                                alt="Template Logo"
                                                                class="max-w-full max-h-full object-contain">
                                                        </span>
                                                    </div>
                                                </div>
                                            @elseif ($template->show_logo)
                                                <div class="text-center mb-2">
                                                    <div
                                                        class="w-50 h-50 bg-gray-300 rounded mx-auto flex items-center justify-center">
                                                        <span class="text-xs">
                                                            <img src="{{ asset('logo.png') }}" alt="Default Logo">
                                                        </span>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-500">Logo hidden</span>
                                            @endif
                                            <div class="text-center font-semibold">
                                                {{ $template->approved_invoice_title ?? 'INVOICE' }}</div>
                                        </div>
                                    </div>

                                    <!-- Template Settings Summary -->
                                    <div class="text-xs text-gray-600 space-y-1 mb-4">
                                        <div><strong>Show:</strong>
                                            @if ($template->show_column_heading)
                                                Column headings,
                                            @endif
                                            @if ($template->show_item_code)
                                                Item code,
                                            @endif
                                            @if ($template->show_logo)
                                                Logo
                                            @endif
                                        </div>
                                        <div><strong>Logo Align:</strong>
                                            {{ ucfirst($template->logo_alignment ?? 'Right') }}</div>
                                        <div><strong>Measure:</strong> {{ strtoupper($template->measures_in ?? 'cm') }}
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('account.organization-settings.invoice-templates.show', $template->id) }}"
                                            class="flex-1 px-3 py-2 bg-blue-50 text-blue-700 text-xs font-medium rounded hover:bg-blue-100 transition-colors text-center">
                                            View
                                        </a>
                                        <a href="{{ route('account.organization-settings.invoice-templates.edit', $template->id) }}"
                                            class="flex-1 px-3 py-2 bg-gray-50 text-gray-700 text-xs font-medium rounded hover:bg-gray-100 transition-colors text-center">
                                            Edit
                                        </a>
                                        <div class="flex gap-1">
                                            <form
                                                action="{{ route('account.organization-settings.invoice-templates.duplicate', $template->id) }}"
                                                method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="px-2 py-2 bg-green-50 text-green-700 text-xs font-medium rounded hover:bg-green-100 transition-colors"
                                                    title="Duplicate">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </form>
                                            <form
                                                action="{{ route('account.organization-settings.invoice-templates.destroy', $template->id) }}"
                                                method="POST" class="inline"
                                                onsubmit="return confirm('Are you sure you want to delete this template?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-2 py-2 bg-red-50 text-red-700 text-xs font-medium rounded hover:bg-red-100 transition-colors"
                                                    title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12">
                                <div class="text-gray-400 mb-4">
                                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No Invoice Templates</h3>
                                <p class="text-gray-500 mb-4">Get started by creating your first invoice template.</p>
                                <a href="{{ route('account.organization-settings.invoice-templates.create') }}"
                                    class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded hover:bg-[#006bb0] transition-all">
                                    Create Template
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
