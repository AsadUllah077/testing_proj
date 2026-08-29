@extends('layouts.auth')
@section('title', 'Smart Bank Rules')

@section('content')
    <div class="p-4 space-y-4">
        <div class="bg-white border border-gray-200 rounded-[5px]">
            <div class="px-5 py-4"><h1 class="text-lg font-bold text-gray-800">Smart Bank Rules</h1></div>
            @include('pages.account.control-center.partials.tabs')
        </div>

        <form method="POST" action="{{ route('account.bank-rules.store') }}" class="bg-white border border-gray-200 rounded-[5px] p-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Rule name</label><input name="name" value="{{ old('name') }}" required class="w-full h-10 border border-gray-300 rounded-[3px] px-3 text-sm"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Bank account</label><select name="bank_account_id" required class="select2 w-full"><option value="">Select</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->name }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Payee pattern</label><input name="payee" value="{{ old('payee') }}" required class="w-full h-10 border border-gray-300 rounded-[3px] px-3 text-sm"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Match</label><select name="match_type" class="w-full h-10 border border-gray-300 rounded-[3px] px-3 text-sm"><option value="exact">Exact</option><option value="contains">Contains</option><option value="starts_with">Starts with</option></select></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Direction</label><select name="direction" class="w-full h-10 border border-gray-300 rounded-[3px] px-3 text-sm"><option value="both">Money in or out</option><option value="money_in">Money in</option><option value="money_out">Money out</option></select></div>
                <div class="grid grid-cols-2 gap-2"><div><label class="block text-xs font-semibold text-gray-600 mb-1">Minimum</label><input type="number" step="0.01" min="0" name="min_amount" class="w-full h-10 border border-gray-300 rounded-[3px] px-3 text-sm"></div><div><label class="block text-xs font-semibold text-gray-600 mb-1">Maximum</label><input type="number" step="0.01" min="0" name="max_amount" class="w-full h-10 border border-gray-300 rounded-[3px] px-3 text-sm"></div></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Suggested account</label><select name="contra_account_id" required class="select2 w-full"><option value="">Select</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->name }}</option>@endforeach</select></div>
                <div class="flex items-end gap-3"><input type="hidden" name="priority" value="100"><input type="hidden" name="is_active" value="1"><button class="w-full h-10 bg-[#0078C8] text-white rounded-[3px] text-sm"><i class="fa-solid fa-plus mr-2"></i>Add Rule</button></div>
            </div>
        </form>

        <section class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
            <div class="overflow-x-auto"><table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="px-4 py-3 text-left">Rule</th><th class="px-4 py-3 text-left">Bank</th><th class="px-4 py-3 text-left">Condition</th><th class="px-4 py-3 text-left">Suggested Account</th><th class="px-4 py-3 text-center">Used</th><th class="px-4 py-3 text-center">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rules as $rule)
                        <tr><td class="px-4 py-3 font-medium text-gray-800">{{ $rule->name ?: $rule->payee }}</td><td class="px-4 py-3">{{ $rule->bankAccount?->name }}</td><td class="px-4 py-3"><span class="text-xs text-gray-500">{{ str_replace('_', ' ', $rule->match_type) }}</span><br>{{ $rule->payee }}</td><td class="px-4 py-3">{{ $rule->contraAccount?->name }}</td><td class="px-4 py-3 text-center">{{ $rule->usage_count }}</td><td class="px-4 py-3 text-center"><span class="px-2 py-1 text-xs rounded-full {{ $rule->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $rule->is_active ? 'Active' : 'Paused' }}</span></td><td class="px-4 py-3 text-right"><div class="flex justify-end gap-2"><details class="relative"><summary class="list-none w-8 h-8 inline-flex items-center justify-center border border-gray-300 rounded-[3px] cursor-pointer" title="Edit"><i class="fa-solid fa-pen text-xs"></i></summary><div class="absolute right-0 z-20 mt-2 w-80 bg-white border border-gray-200 shadow-lg rounded-[5px] p-4 text-left"><form method="POST" action="{{ route('account.bank-rules.update', $rule) }}" class="space-y-2">@csrf @method('PUT')<input name="name" value="{{ $rule->name ?: $rule->payee }}" required class="w-full h-9 border rounded-[3px] px-2"><input name="payee" value="{{ $rule->payee }}" required class="w-full h-9 border rounded-[3px] px-2"><input type="hidden" name="bank_account_id" value="{{ $rule->bank_account_id }}"><input type="hidden" name="contra_account_id" value="{{ $rule->contra_account_id }}"><input type="hidden" name="min_amount" value="{{ $rule->min_amount }}"><input type="hidden" name="max_amount" value="{{ $rule->max_amount }}"><select name="match_type" class="w-full h-9 border rounded-[3px] px-2"><option value="exact" @selected($rule->match_type === 'exact')>Exact</option><option value="contains" @selected($rule->match_type === 'contains')>Contains</option><option value="starts_with" @selected($rule->match_type === 'starts_with')>Starts with</option></select><select name="direction" class="w-full h-9 border rounded-[3px] px-2"><option value="both" @selected($rule->direction === 'both')>Both</option><option value="money_in" @selected($rule->direction === 'money_in')>Money in</option><option value="money_out" @selected($rule->direction === 'money_out')>Money out</option></select><input type="number" name="priority" value="{{ $rule->priority }}" min="1" max="9999" class="w-full h-9 border rounded-[3px] px-2"><label class="flex items-center gap-2 text-xs"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active)> Active</label><button class="w-full h-9 bg-[#0078C8] text-white rounded-[3px]">Save</button></form></div></details><form method="POST" action="{{ route('account.bank-rules.destroy', $rule) }}" onsubmit="return confirm('Remove this bank rule?')">@csrf @method('DELETE')<button class="w-8 h-8 border border-red-200 text-red-600 rounded-[3px]" title="Delete"><i class="fa-solid fa-trash text-xs"></i></button></form></div></td></tr>
                    @empty<tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No bank rules found.</td></tr>@endforelse
                </tbody>
            </table></div>
            <div class="p-3 border-t">{{ $rules->links() }}</div>
        </section>
    </div>
@endsection
