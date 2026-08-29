@php
    $accountsByCategory = \App\Models\ChartOfAccount::forCompany(loggedCompanyId()['company_id'] ?? null)
        ->orderBy('account_number')
        ->get()
        ->groupBy('category');
@endphp
<select name="{{ $name ?? 'parent_id' }}" id="{{ $id ?? '' }}" data-filter
    class="select2 w-full bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
    <option value="">-- Select Account --</option>

    @foreach ($accountsByCategory as $category => $accounts)
        <optgroup label="{{ $category }}">
            @foreach ($accounts as $acc)
                <option value="{{ $acc->id }}" {{ ($selected ?? '') == $acc->id ? 'selected' : '' }}>
                    {{ $acc->account_number }} - {{ $acc->name }}
                </option>
            @endforeach
        </optgroup>
    @endforeach
</select>
