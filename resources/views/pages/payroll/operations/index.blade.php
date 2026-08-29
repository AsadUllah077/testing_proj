@extends('layouts.auth')
@section('title', 'Payroll Operations')

@section('content')
<x-acccount.loader />
<div class="mx-auto px-3 pb-8 space-y-4">
    <div class="bg-white border border-black/10 rounded-md p-5">
        <h1 class="text-xl font-medium">Payroll Operations</h1>
        <div class="flex flex-wrap gap-1 mt-4 border-b">
            @foreach(['settlements' => 'Final Settlements', 'allocations' => 'Cost Allocation', 'scenarios' => 'Scenarios', 'reviews' => 'Compensation', 'variance' => 'Variance', 'bank' => 'Bank Requests'] as $key => $label)
                <a href="{{ route('payroll.operations.index', ['tab' => $key]) }}" class="px-4 py-2 text-sm border-b-2 {{ $tab === $key ? 'border-[#0078C8] text-[#0078C8]' : 'border-transparent text-gray-500' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
    @if(session('success'))<div class="p-3 rounded border border-green-200 bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="p-3 rounded border border-red-200 bg-red-50 text-red-800 text-sm">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="p-3 rounded border border-red-200 bg-red-50 text-red-800 text-sm">{{ $errors->first() }}</div>@endif

    @if($tab === 'settlements')
        <div class="grid grid-cols-1 xl:grid-cols-[440px_minmax(0,1fr)] gap-4 items-start">
            <form method="POST" action="{{ route('payroll.operations.settlements.store') }}" class="bg-white border border-black/10 rounded-md p-5 space-y-4">
                @csrf
                <h2 class="font-medium">New Final Settlement</h2>
                <select name="employee_id" class="select2 w-full border rounded p-2 text-sm" required><option value="">Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->full_name }}</option>@endforeach</select>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><label class="text-xs text-gray-500">Last Working Date</label><input type="date" name="last_working_date" required class="date w-full border rounded p-2 text-sm"></div>
                    <div><label class="text-xs text-gray-500">Settlement Date</label><input type="date" name="settlement_date" required class="date w-full border rounded p-2 text-sm"></div>
                </div>
                <div id="settlementLines" class="space-y-3"></div>
                <button type="button" id="addSettlementLine" class="text-xs text-[#0078C8]"><i class="fa-solid fa-plus mr-1"></i> Add line</button>
                <button class="w-full py-2 bg-[#0078C8] text-white rounded text-xs font-bold">CREATE DRAFT</button>
            </form>
            <div class="bg-white border border-black/10 rounded-md overflow-x-auto">
                <table class="w-full text-sm"><thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase"><tr><th class="p-3">Employee</th><th class="p-3">Date</th><th class="p-3">Gross</th><th class="p-3">Deductions</th><th class="p-3">Net</th><th class="p-3">Status</th><th class="p-3">Action</th></tr></thead>
                    <tbody class="divide-y">@forelse($finalSettlements as $item)<tr><td class="p-3">{{ $item->employee?->full_name }}</td><td class="p-3">{{ $item->settlement_date->format('Y-m-d') }}</td><td class="p-3">{{ number_format($item->gross_amount, 2) }}</td><td class="p-3">{{ number_format($item->deduction_amount, 2) }}</td><td class="p-3">{{ number_format($item->net_amount, 2) }}</td><td class="p-3 capitalize">{{ str_replace('_',' ',$item->status) }}</td><td class="p-3"><div class="flex gap-2">@if(in_array($item->status,['draft','hr_reviewed','payroll_reviewed']))<form method="POST" action="{{ route('payroll.operations.settlements.approve', $item) }}">@csrf<input type="hidden" name="stage" value="{{ $item->status==='draft'?'hr':($item->status==='hr_reviewed'?'payroll':'finance') }}"><button class="text-green-600">{{ $item->status==='draft'?'HR Review':($item->status==='hr_reviewed'?'Payroll Review':'Finance Approve') }}</button></form>@elseif($item->status === 'approved')<form method="POST" action="{{ route('payroll.operations.settlements.post', $item) }}">@csrf<button class="text-[#0078C8]">Post</button></form>@else<span class="text-xs text-gray-500">{{ $item->unique_no }}</span>@endif</div></td></tr>@empty<tr><td colspan="7" class="p-5 text-center text-gray-500">No settlements.</td></tr>@endforelse</tbody>
                </table>
            </div>
        </div>
    @elseif($tab === 'allocations')
        <div class="bg-white border rounded-md p-4"><form method="GET" class="flex gap-2"><input type="hidden" name="tab" value="allocations"><select name="pay_run_id" class="border rounded p-2 text-sm"><option value="">Allocated payroll report</option>@foreach($payRuns as $run)<option value="{{ $run->id }}" @selected(request('pay_run_id')==$run->id)>{{ $run->unique_no }} · {{ $run->period_end->format('Y-m-d') }}</option>@endforeach</select><button class="bg-[#0078C8] text-white rounded px-4 text-xs">RUN REPORT</button></form>
            @if($allocationReport->isNotEmpty())<div class="overflow-x-auto mt-4"><table class="w-full text-sm"><thead class="text-left bg-gray-50"><tr><th class="p-2">Department</th><th class="p-2">Cost Center</th><th class="p-2">Allocated Gross</th><th class="p-2">Allocated Net</th></tr></thead><tbody>@foreach($allocationReport as $row)<tr class="border-t"><td class="p-2">{{ $row['department'] }}</td><td class="p-2">{{ $row['cost_center'] }}</td><td class="p-2">{{ number_format($row['gross'],2) }}</td><td class="p-2">{{ number_format($row['net'],2) }}</td></tr>@endforeach</tbody></table></div>@endif
        </div>
        <div class="grid grid-cols-1 xl:grid-cols-[420px_minmax(0,1fr)] gap-4 items-start">
            <form method="POST" action="{{ route('payroll.operations.allocations.store') }}" class="bg-white border rounded-md p-5 space-y-3">@csrf<h2 class="font-medium">Allocate Employee Cost</h2>
                <select name="employee_id" required class="select2 w-full border rounded p-2"><option value="">Employee</option>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select>
                <div class="grid grid-cols-2 gap-2"><input type="date" name="effective_from" required class="date border rounded p-2 text-sm"><input type="date" name="effective_to" class="date border rounded p-2 text-sm"></div>
                <div id="allocationLines" class="space-y-2"></div><button type="button" id="addAllocationLine" class="text-xs text-[#0078C8]">+ Add allocation</button><p class="text-xs text-gray-500">All rows must total 100%.</p><button class="w-full bg-[#0078C8] text-white rounded py-2 text-xs">SAVE ALLOCATION</button>
            </form>
            <div class="bg-white border rounded-md overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 text-left"><tr><th class="p-3">Employee</th><th class="p-3">Department</th><th class="p-3">Cost Center</th><th class="p-3">Branch / Project</th><th class="p-3">Location / Funding</th><th class="p-3">Percentage</th><th class="p-3">Effective</th></tr></thead><tbody class="divide-y">@foreach($allocations as $a)<tr><td class="p-3">{{ $a->employee?->full_name }}</td><td class="p-3">{{ $a->department?->department }}</td><td class="p-3">{{ $a->cost_center ?: '-' }}</td><td class="p-3">{{ $a->branch ?: '-' }} / {{ $a->project_code ?: '-' }}</td><td class="p-3">{{ $a->location ?: '-' }} / {{ $a->funding_source ?: '-' }}</td><td class="p-3">{{ number_format($a->percentage, 2) }}%</td><td class="p-3">{{ $a->effective_from->format('Y-m-d') }}</td></tr>@endforeach</tbody></table></div>
        </div>
    @elseif($tab === 'scenarios')
        <div class="grid grid-cols-1 xl:grid-cols-[420px_minmax(0,1fr)] gap-4 items-start">
            <form method="POST" action="{{ route('payroll.operations.scenarios.store') }}" class="bg-white border rounded-md p-5 space-y-3">@csrf<h2 class="font-medium">Payroll Scenario</h2><input name="name" required placeholder="Scenario name" class="w-full border rounded p-2"><textarea name="description" placeholder="Description" class="w-full border rounded p-2"></textarea>
                @foreach(['salary_increase_percentage'=>'Salary increase %','new_hires'=>'New hires','average_new_hire_salary'=>'Average new-hire salary','bonus_pool'=>'Annual bonus pool','turnover_percentage'=>'Turnover %','overtime_increase_percentage'=>'Overtime change %','allowance_change_percentage'=>'Allowance change %','tax_change_percentage'=>'Tax change %','employer_cost_percentage'=>'Employer cost %','annual_budget'=>'Annual payroll budget'] as $name=>$label)<div><label class="text-xs text-gray-500">{{ $label }}</label><input type="number" step="0.01" name="{{ $name }}" value="0" required class="w-full border rounded p-2"></div>@endforeach<button class="w-full bg-[#0078C8] text-white rounded py-2 text-xs">CALCULATE SCENARIO</button>
            </form>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">@foreach($scenarios as $s)<div class="bg-white border rounded-md p-4"><div class="font-medium">{{ $s->name }}</div><div class="grid grid-cols-2 gap-3 mt-3 text-sm"><div><span class="text-gray-500">Baseline annual</span><div>{{ number_format($s->results['baseline_annual'], 2) }}</div></div><div><span class="text-gray-500">Projected gross</span><div>{{ number_format($s->results['projected_annual'], 2) }}</div></div><div><span class="text-gray-500">Projected tax</span><div>{{ number_format($s->results['projected_annual_tax'] ?? 0, 2) }}</div></div><div><span class="text-gray-500">Projected net</span><div>{{ number_format($s->results['projected_annual_net'] ?? 0, 2) }}</div></div><div><span class="text-gray-500">Employer budget</span><div>{{ number_format($s->results['total_employer_budget'] ?? $s->results['projected_annual'], 2) }}</div></div><div><span class="text-gray-500">Budget variance</span><div>{{ number_format($s->results['budget_variance'] ?? 0, 2) }}</div></div><div><span class="text-gray-500">Difference</span><div>{{ number_format($s->results['annual_difference'], 2) }}</div></div><div><span class="text-gray-500">Headcount</span><div>{{ $s->results['projected_headcount'] }}</div></div></div></div>@endforeach</div>
        </div>
    @elseif($tab === 'reviews')
        <div class="grid grid-cols-1 xl:grid-cols-[400px_minmax(0,1fr)] gap-4 items-start"><form method="POST" action="{{ route('payroll.operations.reviews.store') }}" class="bg-white border rounded-md p-5 space-y-3">@csrf<h2 class="font-medium">Compensation Review</h2><select name="employee_id" required class="select2 w-full border rounded p-2"><option value="">Employee</option>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select><input type="number" name="market_benchmark" min="0" step="0.01" placeholder="Market benchmark salary" class="w-full border rounded p-2"><input type="number" name="proposed_salary" min="0" step="0.01" required placeholder="Proposed salary" class="w-full border rounded p-2"><input type="date" name="effective_date" required class="date w-full border rounded p-2"><textarea name="manager_recommendation" required placeholder="Manager recommendation" class="w-full border rounded p-2"></textarea><textarea name="hr_recommendation" required placeholder="HR recommendation" class="w-full border rounded p-2"></textarea><textarea name="reason" required placeholder="Business reason" class="w-full border rounded p-2"></textarea><button class="w-full bg-[#0078C8] text-white rounded py-2 text-xs">CREATE REVIEW</button></form>
            <div class="bg-white border rounded-md overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 text-left"><tr><th class="p-3">Employee</th><th class="p-3">Current / Market</th><th class="p-3">Proposed</th><th class="p-3">Annual Impact</th><th class="p-3">Effective</th><th class="p-3">Status / Action</th></tr></thead><tbody class="divide-y">@foreach($reviews as $r)<tr><td class="p-3">{{ $r->employee?->full_name }}</td><td class="p-3">{{ number_format($r->current_salary,2) }} / {{ number_format($r->market_benchmark,2) }}</td><td class="p-3">{{ number_format($r->proposed_salary,2) }}</td><td class="p-3">{{ number_format($r->annual_budget_impact,2) }}</td><td class="p-3">{{ $r->effective_date->format('Y-m-d') }}</td><td class="p-3">@if($r->status==='draft')<form method="POST" action="{{ route('payroll.operations.reviews.approve',$r) }}" class="flex gap-1">@csrf<input type="number" name="approved_salary" value="{{ $r->proposed_salary }}" class="w-28 border rounded p-1"><button class="text-green-600">Approve</button></form>@elseif($r->status==='approved')<div class="flex gap-2"><form method="POST" action="{{ route('payroll.operations.reviews.apply',$r) }}">@csrf<button class="text-[#0078C8]">Apply</button></form><a class="text-gray-600" href="{{ route('payroll.operations.reviews.increment-letter',$r) }}">Letter</a></div>@else<div class="flex gap-2"><span class="capitalize">{{ $r->status }}</span><a class="text-gray-600" href="{{ route('payroll.operations.reviews.increment-letter',$r) }}">Letter</a></div>@endif</td></tr>@endforeach</tbody></table></div></div>
    @elseif($tab === 'variance')
        <div class="bg-white border rounded-md p-4"><form method="GET" class="flex flex-wrap gap-2"><input type="hidden" name="tab" value="variance"><select name="pay_run_id" required class="border rounded p-2 text-sm"><option value="">Select posted pay run</option>@foreach($payRuns as $run)<option value="{{ $run->id }}" @selected(request('pay_run_id')==$run->id)>{{ $run->unique_no }} · {{ $run->period_end->format('Y-m-d') }}</option>@endforeach</select><button class="bg-[#0078C8] text-white rounded px-4 text-xs">COMPARE</button>@if(request('pay_run_id'))<a class="border rounded px-4 py-2 text-xs" href="{{ route('payroll.operations.audit-pack',request('pay_run_id')) }}"><i class="fa-solid fa-file-pdf mr-1"></i> AUDIT PACK</a>@endif</form></div>
        @if($variance->isNotEmpty())<div class="bg-white border rounded-md overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 text-left"><tr><th class="p-3">Employee</th><th class="p-3">Gross</th><th class="p-3">Gross Change</th><th class="p-3">Net</th><th class="p-3">Net Change</th><th class="p-3">Automatic Reasons</th><th class="p-3">Explanation</th></tr></thead><tbody class="divide-y">@foreach($variance as $v)<tr><td class="p-3">{{ $v['employee']?->full_name }}</td><td class="p-3">{{ number_format($v['gross'],2) }}</td><td class="p-3">{{ number_format($v['gross_variance'],2) }}</td><td class="p-3">{{ number_format($v['net'],2) }}</td><td class="p-3">{{ number_format($v['net_variance'],2) }}</td><td class="p-3 min-w-48">@foreach($v['reasons'] as $reason)<div class="text-xs">{{ $reason['name'] }}: {{ $reason['type']==='deduction' ? ($reason['difference'] >= 0 ? '-' : '+') : ($reason['difference'] >= 0 ? '+' : '-') }}{{ number_format(abs($reason['difference']),2) }}</div>@endforeach</td><td class="p-3"><form method="POST" action="{{ route('payroll.operations.variance.comment',request('pay_run_id')) }}" class="flex gap-1">@csrf<input type="hidden" name="employee_id" value="{{ $v['employee']->id }}"><input name="comment" value="{{ $v['comment'] }}" required class="border rounded p-1 min-w-48"><button class="text-[#0078C8]">Save</button></form></td></tr>@endforeach</tbody></table></div>@endif
    @else
        <div class="bg-white border rounded-md overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 text-left"><tr><th class="p-3">Employee</th><th class="p-3">Bank</th><th class="p-3">Account / IBAN</th><th class="p-3">Status</th><th class="p-3">Review</th></tr></thead><tbody class="divide-y">@foreach($bankRequests as $b)<tr><td class="p-3">{{ $b->employee?->full_name }}</td><td class="p-3">{{ $b->bank_name }}</td><td class="p-3">{{ $b->bank_account_number }}<div class="text-xs text-gray-500">{{ $b->bank_iban }}</div></td><td class="p-3 capitalize">{{ $b->status }}</td><td class="p-3">@if($b->status==='pending')<form method="POST" action="{{ route('payroll.operations.bank-requests.review',$b) }}" class="flex gap-1">@csrf<input name="review_notes" placeholder="Notes" class="border rounded p-1"><button name="status" value="approved" class="text-green-600">Approve</button><button name="status" value="rejected" class="text-red-600">Reject</button></form>@endif</td></tr>@endforeach</tbody></table></div>
    @endif
</div>
@endsection

@push('scripts')
<script>
const accounts = @json($accounts->map(fn($a) => ['id'=>$a->id,'label'=>trim(($a->account_number ? $a->account_number.' - ' : '').$a->name),'category'=>$a->category]));
const departments = @json($departments->map(fn($d) => ['id'=>$d->id,'name'=>$d->department]));
function settlementAccountOptions(type){
    const categories = type === 'earning' ? ['Expenses','COGS'] : ['Liabilities','Assets'];
    const matching = accounts.filter(account => categories.includes(account.category));
    if (!matching.length) {
        return '<option value="">No suitable ledger account configured</option>';
    }

    return matching.map(account => `<option value="${account.id}">${account.label}</option>`).join('');
}
function updateSettlementAccounts(typeSelect){
    const accountSelect = typeSelect.closest('.settlement-line').querySelector('select[name="account_id[]"]');
    accountSelect.innerHTML = settlementAccountOptions(typeSelect.value);
    $(accountSelect).trigger('change');
}
function settlementLine(){return `<div class="settlement-line w-full min-w-0 rounded-md border border-gray-200 bg-gray-50 p-3 space-y-2">
    <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_120px_32px] gap-2 items-end">
        <label class="min-w-0"><span class="block mb-1 text-[11px] text-gray-500">Description</span><input name="description[]" required placeholder="e.g. Salary through last day" class="block w-full min-w-0 border rounded p-2 text-xs bg-white"></label>
        <label><span class="block mb-1 text-[11px] text-gray-500">Type</span><select name="type[]" onchange="updateSettlementAccounts(this)" class="block w-full border rounded p-2 text-xs bg-white"><option value="earning">Earning</option><option value="deduction">Deduction</option></select></label>
        <button type="button" onclick="this.closest('.settlement-line').remove()" title="Remove line" class="settlement-remove h-9 w-8 rounded text-gray-500 hover:bg-red-50 hover:text-red-600"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_120px] gap-2">
        <label class="min-w-0"><span class="block mb-1 text-[11px] text-gray-500">Ledger Account</span><select name="account_id[]" required class="settlement-account select2 block w-full min-w-0 border rounded p-2 text-xs bg-white">${settlementAccountOptions('earning')}</select></label>
        <label><span class="block mb-1 text-[11px] text-gray-500">Amount</span><input name="amount[]" type="number" min="0.01" step="0.01" required placeholder="0.00" class="block w-full border rounded p-2 text-xs bg-white text-right"></label>
    </div>
</div>`}
function allocationLine(){return `<div class="grid grid-cols-2 gap-1 border-b pb-2"><select name="department_id[]" required class="border rounded p-2 text-xs">${departments.map(d=>`<option value="${d.id}">${d.name}</option>`).join('')}</select><input name="percentage[]" type="number" min="0.01" max="100" step="0.01" required placeholder="Percentage" class="border rounded p-2 text-xs"><input name="cost_center[]" placeholder="Cost center" class="border rounded p-2 text-xs"><input name="branch[]" placeholder="Branch" class="border rounded p-2 text-xs"><input name="project_code[]" placeholder="Project" class="border rounded p-2 text-xs"><input name="location[]" placeholder="Location" class="border rounded p-2 text-xs"><input name="funding_source[]" placeholder="Funding source" class="border rounded p-2 text-xs"><button type="button" onclick="this.parentElement.remove()" class="text-red-500 text-xs">Remove allocation</button></div>`}
document.getElementById('addSettlementLine')?.addEventListener('click',()=>{
    const container = document.getElementById('settlementLines');
    container.insertAdjacentHTML('beforeend',settlementLine());
    $(container.lastElementChild).find('.settlement-account').select2({width:'100%'});
});
document.getElementById('addAllocationLine')?.addEventListener('click',()=>document.getElementById('allocationLines').insertAdjacentHTML('beforeend',allocationLine()));
if(document.getElementById('settlementLines')) document.getElementById('settlementLines').innerHTML=settlementLine();
if(document.getElementById('allocationLines')) document.getElementById('allocationLines').innerHTML=allocationLine();
$('.select2').select2({width:'100%'});
</script>
@endpush
