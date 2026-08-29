@extends('layouts.auth')

@section('title', 'Payroll Users')

@section('content')
    <div class="min-h-screen bg-gray-50/30 py-8">
        <div class="mx-auto w-full px-4 sm:px-6 lg:px-8">
            <div class="mb-5 rounded-xl bg-white p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900">Payroll Users</h1>
                        <p class="mt-1 text-sm text-gray-500">Manage company users, payroll roles, and payroll-only permissions.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('payroll.organization-settings.roles.create') }}"
                            class="rounded-xl border border-[#0078C8] px-4 py-2.5 text-sm font-bold text-[#0078C8] hover:bg-blue-50">
                            Create Payroll Role
                        </a>
                        <a href="{{ route('payroll.organization-settings.users.export') }}"
                            class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">
                            Export PDF
                        </a>
                        <a href="{{ route('payroll.organization-settings.users.create') }}"
                            class="rounded-xl bg-[#0078C8] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#006bb0]">
                            Create Payroll User
                        </a>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white">
                <form method="GET" class="grid grid-cols-1 gap-4 border-b border-gray-100 bg-gray-50/50 p-5 md:grid-cols-5">
                    <input type="text" name="q" value="{{ request('q', $search) }}" placeholder="Search users"
                        class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm md:col-span-2">
                    <input type="date" name="from_date" value="{{ request('from_date', $from) }}"
                        class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                    <input type="date" name="to_date" value="{{ request('to_date', $to) }}"
                        class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                    <button class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-bold text-white">Filter</button>
                </form>

                <div id="payroll-user-table">
                    @include('pages.payroll.organization-settings.user.table')
                </div>
            </div>
        </div>
    </div>

    <x-delete-confirmation />
@endsection

@push('scripts')
    <script>
        let payrollDeleteTarget = null;

        window.confirmDelete = function (button) {
            payrollDeleteTarget = { url: button.dataset.url, row: button.dataset.row };
            document.getElementById('deleteModal')?.classList.remove('hidden');
        };

        document.getElementById('confirmDelete')?.addEventListener('click', async () => {
            if (!payrollDeleteTarget) return;

            const response = await fetch(payrollDeleteTarget.url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            if (response.ok) {
                document.querySelector(payrollDeleteTarget.row)?.remove();
            } else {
                alert(data?.message || data?.error?.[0] || 'Unable to delete user.');
            }
            document.getElementById('deleteModal')?.classList.add('hidden');
            payrollDeleteTarget = null;
        });
    </script>
@endpush
