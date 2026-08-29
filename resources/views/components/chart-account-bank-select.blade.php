<div>
    <select name="{{ $name ?? 'parent_id' }}" id="{{ $id ?? ($name ?? 'account_select') }}"
        class="select2 w-full bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">

        <option value="">-- Select Account --</option>

        @foreach ($accounts as $account)
            <option value="{{ $account->id }}" @selected(($selected ?? null) == $account->id)>
                {{ $account->account_number }} - {{ $account->name }}
            </option>
        @endforeach

    </select>
</div>
