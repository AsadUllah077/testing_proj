@extends('layouts.auth')

@section('title', 'Organization Settings')

@section('content')
<div class="min-h-screen bg-gray-50/30 py-10">
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Section -->
        <div class="mb-12 bg-white p-5 rounded-xl">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight leading-none mb-3">Organization Settings</h1>
            <p class="text-base text-gray-500 font-medium max-w-2xl">Configure your company identity, financial landscape, and team access from a centralized professional hub.</p>
        </div>

        <!-- Settings Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8">
            
            <!-- 1. Organization Profile -->
            <a href="{{ route('account.organization-settings.company', $company->id) }}" 
               class="group relative bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm hover:shadow-2xl hover:shadow-[#0078C8]/10 hover:-translate-y-1 transition-all duration-500 ring-1 ring-black/[0.02]">
                <div class="flex items-start gap-6">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 text-[#0078C8] flex items-center justify-center shrink-0 group-hover:bg-[#0078C8] group-hover:text-white transition-colors duration-500">
                        <span class="material-symbols-outlined text-3xl">corporate_fare</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-[#0078C8] transition-colors">Organization Profile</h3>
                        <p class="text-sm text-gray-500 font-medium leading-relaxed mb-4">
                            Current: <span class="text-gray-900 font-bold tracking-tight">{{ $company->name }}</span>
                        </p>
                        <p class="text-[13px] text-gray-400 font-medium leading-relaxed">
                            Manage your legal entity name, primary contact details, brand logo, and authorized signatures for financial documents.
                        </p>
                    </div>
                </div>
                <div class="absolute top-8 right-8 text-gray-300 group-hover:text-[#0078C8] group-hover:translate-x-1 transition-all">
                    <span class="material-symbols-outlined text-2xl">arrow_forward</span>
                </div>
            </a>

            <!-- 2. Accounting Users -->
            <a href="{{ route('account.organization-settings.users') }}" 
               class="group relative bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm hover:shadow-2xl hover:shadow-[#0078C8]/10 hover:-translate-y-1 transition-all duration-500 ring-1 ring-black/[0.02]">
                <div class="flex items-start gap-6">
                    <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-500">
                        <span class="material-symbols-outlined text-3xl">manage_accounts</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-indigo-600 transition-colors">Accounting Users</h3>
                        <p class="text-sm text-gray-500 font-medium leading-relaxed mb-4">
                            Available: <span class="text-gray-900 font-bold tracking-tight">{{ $accountingUsers->count() }} company users</span>
                        </p>
                        <p class="text-[13px] text-gray-400 font-medium leading-relaxed">
                            Manage existing company users and grant accounting roles and permissions without leaving the accounting area.
                        </p>
                    </div>
                </div>
                <div class="absolute top-8 right-8 text-gray-300 group-hover:text-indigo-600 group-hover:translate-x-1 transition-all">
                    <span class="material-symbols-outlined text-2xl">arrow_forward</span>
                </div>
            </a>

            <!-- 3. Document Branding -->
            <a href="{{ route('account.organization-settings.invoice-templates.index') }}" 
               class="group relative bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm hover:shadow-2xl hover:shadow-[#0078C8]/10 hover:-translate-y-1 transition-all duration-500 ring-1 ring-black/[0.02]">
                <div class="flex items-start gap-6">
                    <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 group-hover:bg-rose-600 group-hover:text-white transition-colors duration-500">
                        <span class="material-symbols-outlined text-3xl">palette</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-rose-600 transition-colors">Document Branding</h3>
                        <p class="text-sm text-gray-500 font-medium leading-relaxed mb-4">
                            Invoice & Template Customization
                        </p>
                        <p class="text-[13px] text-gray-400 font-medium leading-relaxed">
                            Design professional branding themes for invoices and bills. Configure payment services and automated reminders.
                        </p>
                    </div>
                </div>
                <div class="absolute top-8 right-8 text-gray-300 group-hover:text-rose-600 group-hover:translate-x-1 transition-all">
                    <span class="material-symbols-outlined text-2xl">arrow_forward</span>
                </div>
            </a>

            <!-- 4. Invoice Terms -->
            <a href="{{ route('account.organization-settings.invoice-term-conditions.index') }}"
               class="group relative bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm hover:shadow-2xl hover:shadow-[#0078C8]/10 hover:-translate-y-1 transition-all duration-500 ring-1 ring-black/[0.02]">
                <div class="flex items-start gap-6">
                    <div class="w-16 h-16 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 group-hover:bg-cyan-600 group-hover:text-white transition-colors duration-500">
                        <span class="material-symbols-outlined text-3xl">contract</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-cyan-600 transition-colors">Invoice Terms</h3>
                        <p class="text-sm text-gray-500 font-medium leading-relaxed mb-4">
                            Default Terms & Conditions
                        </p>
                        <p class="text-[13px] text-gray-400 font-medium leading-relaxed">
                            Manage fallback terms used on invoices when the selected invoice template does not define its own terms.
                        </p>
                    </div>
                </div>
                <div class="absolute top-8 right-8 text-gray-300 group-hover:text-cyan-600 group-hover:translate-x-1 transition-all">
                    <span class="material-symbols-outlined text-2xl">arrow_forward</span>
                </div>
            </a>

            <!-- 4. Financial Architecture -->
            <a href="{{ route('account.organization-settings.accounting-period.index') }}" 
               class="group relative bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm hover:shadow-2xl hover:shadow-[#0078C8]/10 hover:-translate-y-1 transition-all duration-500 ring-1 ring-black/[0.02]">
                <div class="flex items-start gap-6">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-500">
                        <span class="material-symbols-outlined text-3xl">account_balance_wallet</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-emerald-600 transition-colors">Financial Architecture</h3>
                        <p class="text-sm text-gray-500 font-medium leading-relaxed mb-4">
                            Fiscal Periods & Tax Configurations
                        </p>
                        <p class="text-[13px] text-gray-400 font-medium leading-relaxed">
                            Configure cash and accrual accounting methods, define your fiscal year, and set up fundamental accounting periods.
                        </p>
                    </div>
                </div>
                <div class="absolute top-8 right-8 text-gray-300 group-hover:text-emerald-600 group-hover:translate-x-1 transition-all">
                    <span class="material-symbols-outlined text-2xl">arrow_forward</span>
                </div>
            </a>

        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
</style>
@endsection
