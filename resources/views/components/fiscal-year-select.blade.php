@php
    $fiscalYears = \App\Models\AccountingPeriod::where('company_id', auth()->user()->company_id)
        ->orderBy('start_date', 'desc')
        ->get();
@endphp

<div>
    <label class="block text-xs text-gray-500 mb-1">Fiscal Year</label>
    <select name="fiscal_year" data-filter
        class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
        <option value="">-- Select Fiscal Year --</option>
        @foreach ($fiscalYears as $fy)
            <option value="{{ $fy->id }}" {{ request('fiscal_year') == $fy->id ? 'selected' : '' }}>
                {{ $fy->name }} ({{ $fy->start_date->format('d M Y') }} - {{ $fy->end_date->format('d M Y') }})
            </option>
        @endforeach
    </select>
</div>
