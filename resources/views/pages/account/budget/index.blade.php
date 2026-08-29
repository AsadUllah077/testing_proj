@extends('layouts.auth')
@section('title') Budget Management @endsection

@section('content')
<div class="p-4 mx-auto">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-5 bg-white p-5">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Budget Management</h1>
            <p class="text-xs text-gray-500 mt-0.5">Set budget targets per account per accounting period</p>
        </div>
        <a href="{{ route('account.budget.report') }}{{ request('period_id') ? '?period_id='.request('period_id') : '' }}"
            class="bg-[#0078C8] text-white text-sm font-semibold px-4 py-2 rounded-[3px] flex items-center gap-1.5">
            <i class="fa-solid fa-chart-bar text-sm"></i> View Report
        </a>
    </div>

    {{-- Period Selector --}}
    <form method="GET" class="bg-white border border-gray-200 rounded-[5px] p-4 mb-4 flex items-center gap-3">
        <label class="text-sm font-medium text-gray-600 w-[180px]">Accounting Period:</label>
        <select name="period_id" onchange="this.form.submit()"
            class="select2 border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
            @foreach ($periods as $p)
                <option value="{{ $p->id }}" @selected($period?->id == $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        @if ($period)
            <span class="text-xs text-gray-500 w-[250px]">
                {{ $period->start_date->format('d M Y') }} — {{ $period->end_date->format('d M Y') }}
            </span>
        @endif
    </form>

    @if (!$period)
        <div class="bg-amber-50 border border-amber-200 rounded-[4px] p-4 text-sm text-amber-700">
            No accounting periods found. Please create one in Organization Settings first.
        </div>
    @else
    {{-- Budget Table --}}
    <div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                Budget for {{ $period->name }}
            </h2>
            <p class="text-xs text-gray-400">Click any amount cell to edit inline</p>
        </div>

        <table class="w-full text-sm">
            <thead class="border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Account</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Category</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase w-40">Budget Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Notes</th>
                    <th class="px-4 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="budgetTable">
                @php $currentCategory = null; @endphp
                @foreach ($accounts as $account)
                    @if ($currentCategory !== $account->category)
                        @php $currentCategory = $account->category; @endphp
                        <tr class="bg-gray-50">
                            <td colspan="5" class="px-4 py-2 text-xs font-bold text-gray-500 uppercase tracking-wide">
                                {{ $account->category }}
                            </td>
                        </tr>
                    @endif
                    @php $budget = $budgets[$account->id] ?? null; @endphp
                    <tr class="hover:bg-blue-50/30" data-account-id="{{ $account->id }}">
                        <td class="px-4 py-2.5 text-gray-800">{{ $account->name }}</td>
                        <td class="px-4 py-2.5 text-xs text-gray-500">{{ $account->category }}</td>
                        <td class="px-4 py-2.5 text-right">
                            <input type="number" step="0.01" min="0"
                                class="budget-input w-full text-right border-0 bg-transparent text-sm font-semibold text-gray-800 focus:outline-none focus:bg-white focus:border focus:border-[#0078C8] focus:rounded px-2 py-1"
                                data-account-id="{{ $account->id }}"
                                data-period-id="{{ $period->id }}"
                                value="{{ $budget?->amount ?? '' }}"
                                placeholder="0.00">
                        </td>
                        <td class="px-4 py-2.5">
                            <input type="text"
                                class="notes-input w-full border-0 bg-transparent text-xs text-gray-500 focus:outline-none focus:bg-white focus:border focus:border-gray-300 focus:rounded px-2 py-1"
                                data-account-id="{{ $account->id }}"
                                value="{{ $budget?->notes ?? '' }}"
                                placeholder="Optional note…">
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            @if ($budget)
                            <button type="button" onclick="deleteBudget({{ $budget->id }}, this)"
                                class="text-red-400 hover:text-red-600 text-xs" title="Clear budget">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>

{{-- Toast --}}
<div id="toast"
    class="fixed bottom-4 right-4 hidden bg-emerald-600 text-white text-sm px-4 py-2.5 rounded-[4px] shadow-lg z-50 transition-all">
    Saved
</div>

@push('scripts')
<script>
const UPSERT_URL = '{{ route("account.budget.upsert") }}';
const CSRF       = '{{ csrf_token() }}';

let saveTimers = {};

function showToast(msg = 'Saved') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 2000);
}

function saveBudget(accountId, periodId, amount, notes) {
    fetch(UPSERT_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ accounting_period_id: periodId, account_id: accountId, amount, notes })
    })
    .then(r => r.json())
    .then(d => { if (d.success) showToast(); })
    .catch(() => showToast('Error saving'));
}

function deleteBudget(budgetId, btn) {
    if (!confirm('Remove this budget entry?')) return;
    fetch(`/account/budget/${budgetId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            const row = btn.closest('tr');
            row.querySelector('.budget-input').value = '';
            row.querySelector('.notes-input').value  = '';
            btn.remove();
            showToast('Cleared');
        }
    });
}

document.querySelectorAll('.budget-input, .notes-input').forEach(input => {
    input.addEventListener('input', function () {
        const accountId = this.dataset.accountId;
        const row       = document.querySelector(`tr[data-account-id="${accountId}"]`);
        const amount    = row.querySelector('.budget-input').value || 0;
        const notes     = row.querySelector('.notes-input').value  || '';
        const periodId  = this.dataset.periodId || row.querySelector('.budget-input').dataset.periodId;

        clearTimeout(saveTimers[accountId]);
        saveTimers[accountId] = setTimeout(() => saveBudget(accountId, periodId, amount, notes), 600);
    });
});
</script>
@endpush
@endsection
