@extends('layouts.auth')

@section('title', 'Organization Profile')

@section('content')
    <div class="min-h-screen bg-gray-50/30 py-8">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white p-5 rounded-xl mb-4">
                <!-- Breadcrumbs & Header -->
                <nav class="flex mb-8" aria-label="Breadcrumb">
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
                                <span class="ml-1 text-sm font-semibold text-gray-900">Organization Profile</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                    <div class="flex items-center gap-5">
                        <div
                            class="w-16 h-16 bg-white rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center p-2">
                            @if ($company->logo)
                                <img src="{{ asset('storage/' . $company->logo) }}" alt="Logo"
                                    class="max-w-full max-h-full object-contain">
                            @else
                                <span class="material-symbols-outlined text-[#0078C8] text-3xl">corporate_fare</span>
                            @endif
                        </div>
                        <div>
                            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $company->name }}</h1>
                            <p class="text-sm text-gray-500 font-medium">Manage your organization's core identification and
                                identity.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('account.organization-settings.company.edit', $company->id) }}"
                            class="inline-flex items-center px-5 py-2.5 bg-[#0078C8] text-white text-sm font-bold rounded-xl shadow-lg shadow-[#0078C8]/20 hover:bg-[#006bb0] hover:shadow-xl transition-all duration-300">
                            <span class="material-symbols-outlined mr-2 text-lg">edit</span>
                            Edit Profile
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                <!-- Left Side: Detailed Info -->
                <div class="lg:col-span-8 space-y-8">

                    <!-- Identity & Localization -->
                    <div
                        class="bg-white rounded-3xl shadow-sm border border-gray-100/50 overflow-hidden ring-1 ring-black/[0.03]">
                        <div
                            class="px-8 py-6 border-b border-gray-50 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center">
                                <span
                                    class="w-8 h-8 rounded-lg bg-blue-50 text-[#0078C8] flex items-center justify-center mr-3">
                                    <span class="material-symbols-outlined text-lg">database</span>
                                </span>
                                Core Identity
                            </h3>
                        </div>
                        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                            <div class="group">
                                <label
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em] mb-2">Organization
                                    Name</label>
                                <div
                                    class="text-base font-semibold text-gray-900 group-hover:text-[#0078C8] transition-colors">
                                    {{ $company->name }}</div>
                            </div>
                            <div class="group">
                                <label
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em] mb-2">Base
                                    Currency</label>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2 py-0.5 rounded-md bg-green-50 text-green-700 text-xs font-bold border border-green-100">
                                        {{ $company->currency->short_name ?? 'N/A' }}
                                    </span>
                                    <span
                                        class="text-base font-semibold text-gray-900">{{ $company->currency->name ?? 'Not configured' }}</span>
                                </div>
                            </div>
                            <div class="group">
                                <label
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em] mb-2">Country</label>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-base font-semibold text-gray-900">{{ $company->country->name ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="group">
                                <label
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em] mb-2">State
                                    / Province</label>
                                <div class="text-base font-semibold text-gray-900">{{ $company->state->name ?? '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Communication -->
                    <div
                        class="bg-white rounded-3xl shadow-sm border border-gray-100/50 overflow-hidden ring-1 ring-black/[0.03]">
                        <div
                            class="px-8 py-6 border-b border-gray-50 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center">
                                <span
                                    class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mr-3">
                                    <span class="material-symbols-outlined text-lg">alternate_email</span>
                                </span>
                                Communication Channels
                            </h3>
                        </div>
                        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                            <div class="group">
                                <label
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em] mb-2">Primary
                                    Email</label>
                                <a href="mailto:{{ $company->email }}"
                                    class="text-base font-semibold text-[#0078C8] hover:underline flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg opacity-70">mail</span>
                                    {{ $company->email }}
                                </a>
                            </div>
                            <div class="group">
                                <label
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em] mb-2">Business
                                    Phone</label>
                                <div class="text-base font-semibold text-gray-900 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg opacity-40">call</span>
                                    {{ $company->phone ?? 'Not provided' }}
                                </div>
                            </div>
                            <div class="group">
                                <label
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-[0.1em] mb-2">Direct
                                    Mobile</label>
                                <div class="text-base font-semibold text-gray-900 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg opacity-40">smartphone</span>
                                    {{ $company->mobile ?? 'Not provided' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Visual Identity -->
                <div class="lg:col-span-4 space-y-8">

                    <!-- Professional Logo -->
                    <div
                        class="bg-white rounded-3xl shadow-sm border border-gray-100/50 overflow-hidden ring-1 ring-black/[0.03] flex flex-col items-center">
                        <div class="w-full px-8 py-6 border-b border-gray-50 bg-gray-50/30">
                            <h3 class="text-sm font-bold text-gray-900 text-center uppercase tracking-widest">Brand Mark
                            </h3>
                        </div>
                        <div class="p-10 w-full flex flex-col items-center">
                            <div
                                class="relative group w-48 h-48 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden transition-all duration-300 hover:border-[#0078C8]/30">
                                @if ($company->logo)
                                    <img src="{{ asset('storage/' . $company->logo) }}"
                                        class="max-w-[80%] max-h-[80%] object-contain scale-110 group-hover:scale-125 transition-transform duration-500">
                                @else
                                    <div class="text-center">
                                        <span
                                            class="material-symbols-outlined text-gray-300 text-5xl mb-2">image_not_supported</span>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">No Logo
                                        </p>
                                    </div>
                                @endif
                            </div>
                            <p class="mt-6 text-[10px] font-medium text-gray-400 text-center leading-relaxed italic">
                                Used across all financial documents, <br> invoices, and reports.
                            </p>
                        </div>
                    </div>

                    <!-- Authored Seal -->
                    <div
                        class="bg-white rounded-3xl shadow-sm border border-gray-100/50 overflow-hidden ring-1 ring-black/[0.03] flex flex-col items-center">
                        <div class="w-full px-8 py-6 border-b border-gray-50 bg-gray-50/30">
                            <h3 class="text-sm font-bold text-gray-900 text-center uppercase tracking-widest">Authorized
                                Signature</h3>
                        </div>
                        <div class="p-10 w-full flex flex-col items-center">
                            <div
                                class="relative group w-full h-32 bg-white rounded-2xl border border-gray-100 flex items-center justify-center overflow-hidden">
                                @if ($company->signature)
                                    <img src="{{ asset('storage/' . $company->signature) }}"
                                        class="max-w-[90%] max-h-[80%] object-contain contrast-125 transition-all duration-300 group-hover:scale-105">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-white/20 to-transparent pointer-events-none">
                                    </div>
                                @else
                                    <div class="text-center opacity-30">
                                        <span class="material-symbols-outlined text-gray-400 text-4xl">draw</span>
                                        <p class="text-[10px] font-bold mt-1 uppercase">Pending</p>
                                    </div>
                                @endif
                            </div>
                            <div
                                class="mt-6 flex items-center gap-2 text-[11px] font-bold text-green-600 bg-green-50 px-3 py-1 rounded-full uppercase tracking-tighter">
                                <span class="material-symbols-outlined text-xs">verified</span>
                                Official Signature
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .tracking-tight {
            letter-spacing: -0.025em;
        }

        .tracking-tightest {
            letter-spacing: -0.05em;
        }
    </style>
@endsection
