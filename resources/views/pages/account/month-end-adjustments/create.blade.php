@extends('layouts.auth')
@section('title', 'Prepare Month-End Adjustment')

@section('content')
    <div class="p-4 space-y-4">
        <div class="bg-white border border-gray-200 rounded-[5px] px-5 py-4 flex justify-between items-center"><h1 class="text-lg font-bold text-gray-800">Prepare Adjustment</h1><a href="{{ route('account.month-end-adjustments.index', ['period_id' => $period?->id]) }}" class="w-9 h-9 inline-flex items-center justify-center border border-gray-300 rounded-[3px]" title="Back"><i class="fa-solid fa-arrow-left"></i></a></div>
        <form method="POST" action="{{ route('account.month-end-adjustments.store') }}" class="bg-white border border-gray-200 rounded-[5px] p-5 space-y-5" id="adjustment-form">
            @csrf
            <input type="hidden" name="fixed_asset_id" value="{{ $asset?->id }}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Accounting period</label><select name="accounting_period_id" required class="select2 w-full"><option value="">Select</option>@foreach($periods as $item)<option value="{{ $item->id }}" @selected($period?->id === $item->id)>{{ $item->name }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Type</label><select name="type" required class="w-full h-10 border border-gray-300 rounded-[3px] px-3 text-sm">@foreach($types as $value => $label)<option value="{{ $value }}" @selected(($asset ? 'depreciation' : old('type')) === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Date</label><input type="date" name="date" value="{{ old('date', $period?->end_date?->toDateString()) }}" required class="date w-full h-10 border border-gray-300 rounded-[3px] px-3 text-sm"></div>
                <div class="md:col-span-3"><label class="block text-xs font-semibold text-gray-600 mb-1">Narration</label><input name="narration" value="{{ old('narration', $asset ? 'Monthly depreciation - '.$asset->name : '') }}" required class="w-full h-10 border border-gray-300 rounded-[3px] px-3 text-sm"></div>
            </div>

            <div class="border border-gray-200 rounded-[5px] overflow-x-auto">
                <table class="w-full text-sm min-w-[760px]"><thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="px-3 py-3 text-left w-1/3">Account</th><th class="px-3 py-3 text-left">Description</th><th class="px-3 py-3 text-right w-36">Debit</th><th class="px-3 py-3 text-right w-36">Credit</th><th class="w-12"></th></tr></thead><tbody id="adjustment-lines"></tbody><tfoot class="bg-gray-50 font-semibold"><tr><td colspan="2" class="px-3 py-3 text-right">Totals</td><td id="debit-total" class="px-3 py-3 text-right">0.00</td><td id="credit-total" class="px-3 py-3 text-right">0.00</td><td></td></tr><tr><td colspan="2" class="px-3 py-2 text-right text-xs text-gray-500">Difference</td><td colspan="2" id="difference" class="px-3 py-2 text-right text-sm font-bold">0.00</td><td></td></tr></tfoot></table>
            </div>
            <div class="flex justify-between"><button type="button" id="add-line" class="h-9 px-3 border border-[#0078C8] text-[#0078C8] rounded-[3px] text-sm"><i class="fa-solid fa-plus mr-2"></i>Add Line</button><button class="h-10 px-5 bg-[#0078C8] text-white rounded-[3px] text-sm"><i class="fa-solid fa-floppy-disk mr-2"></i>Save Draft</button></div>
        </form>
    </div>

    @php
        $adjustmentAccountOptions = $accounts->map(fn($account) => [
            'id' => $account->id,
            'label' => trim(($account->account_number ? $account->account_number . ' - ' : '') . $account->name),
        ])->values();
        $blankAdjustmentLines = [
            ['account_id' => '', 'description' => '', 'debit' => '', 'credit' => ''],
            ['account_id' => '', 'description' => '', 'debit' => '', 'credit' => ''],
        ];
        $initialAdjustmentLines = old('lines', count($prefillLines) ? $prefillLines : $blankAdjustmentLines);
    @endphp
    <script>
        const adjustmentAccounts = {{ Illuminate\Support\Js::from($adjustmentAccountOptions) }};
        const initialLines = {{ Illuminate\Support\Js::from($initialAdjustmentLines) }};
        let lineIndex = 0;
        const options = (selected) => '<option value="">Select account</option>' + adjustmentAccounts.map(a => `<option value="${a.id}" ${String(a.id) === String(selected) ? 'selected' : ''}>${a.label}</option>`).join('');
        const escapeHtml = value => String(value ?? '').replace(/[&<>"'`]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;','`':'&#096;'}[char]));
        function addLine(line = {}) {
            const row = document.createElement('tr'); row.className = 'border-t border-gray-100'; row.innerHTML = `<td class="p-2"><select name="lines[${lineIndex}][account_id]" required class="select2 w-full">${options(line.account_id || '')}</select></td><td class="p-2"><input name="lines[${lineIndex}][description]" value="${escapeHtml(line.description)}" class="w-full h-9 border border-gray-300 rounded-[3px] px-2"></td><td class="p-2"><input type="number" min="0" step="0.01" name="lines[${lineIndex}][debit]" value="${escapeHtml(line.debit)}" class="amount debit w-full h-9 border border-gray-300 rounded-[3px] px-2 text-right"></td><td class="p-2"><input type="number" min="0" step="0.01" name="lines[${lineIndex}][credit]" value="${escapeHtml(line.credit)}" class="amount credit w-full h-9 border border-gray-300 rounded-[3px] px-2 text-right"></td><td class="p-2"><button type="button" class="remove-line w-8 h-8 text-red-600" title="Remove"><i class="fa-solid fa-trash"></i></button></td>`;
            document.getElementById('adjustment-lines').appendChild(row); lineIndex++;
            if (window.jQuery && jQuery.fn.select2) jQuery(row).find('.select2').select2({ width: '100%' });
            row.querySelectorAll('.amount').forEach(input => input.addEventListener('input', calculate)); row.querySelector('.remove-line').addEventListener('click', () => { if (document.querySelectorAll('#adjustment-lines tr').length > 2) { row.remove(); calculate(); } }); calculate();
        }
        function calculate() { const sum = selector => [...document.querySelectorAll(selector)].reduce((t, i) => t + (parseFloat(i.value) || 0), 0); const debit = sum('.debit'), credit = sum('.credit'), diff = debit - credit; document.getElementById('debit-total').textContent = debit.toFixed(2); document.getElementById('credit-total').textContent = credit.toFixed(2); const el = document.getElementById('difference'); el.textContent = diff.toFixed(2); el.className = `px-3 py-2 text-right text-sm font-bold ${Math.abs(diff) <= .01 ? 'text-emerald-600' : 'text-red-600'}`; }
        document.addEventListener('DOMContentLoaded', () => { initialLines.forEach(addLine); document.getElementById('add-line').addEventListener('click', () => addLine()); });
    </script>
@endsection
