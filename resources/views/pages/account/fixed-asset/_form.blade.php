@php $asset = $asset ?? new \App\Models\FixedAsset(); @endphp
<div class="p-4 mx-auto">

    <div class="flex items-center gap-3 mb-5 bg-white p-5">
        <a href="{{ route('account.fixed-asset.index') }}" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&larr;</a>
        <div>
            <h1 class="text-lg font-bold text-gray-800">
                {{ $editing ? 'Edit Asset: ' . $asset->asset_number : 'Add Fixed Asset' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">Fixed Asset Register</p>
        </div>
    </div>

    @if ($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-[4px] p-3 mb-4 text-sm text-red-700">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
    </div>
    @endif

    <form method="POST"
        action="{{ $editing ? route('account.fixed-asset.update', $asset) : route('account.fixed-asset.store') }}">
        @csrf @if ($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- Left 2 cols --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Basic Info --}}
                <div class="bg-white border border-gray-200 rounded-[5px] p-4 space-y-3">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Asset Details</h2>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Asset Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $asset?->name) }}" required
                                class="w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                            <select name="category"
                                class="select2 w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                                <option value="">— Select —</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}" @selected(old('category', $asset?->category) === $cat)>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Depreciation Method <span class="text-red-500">*</span></label>
                            <select name="depreciation_method" required
                                class="select2 w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                                @foreach ($methods as $val => $label)
                                    <option value="{{ $val }}" @selected(old('depreciation_method', $asset?->depreciation_method ?? 'straight_line') === $val)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Purchase Date <span class="text-red-500">*</span></label>
                            <input class="date" type="date" name="purchase_date"
                                value="{{ old('purchase_date', $asset?->purchase_date?->format('Y-m-d')) }}" required
                                class="date w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">In-Service Date <span class="text-red-500">*</span></label>
                            <input class="date" type="date" name="in_service_date"
                                value="{{ old('in_service_date', $asset?->in_service_date?->format('Y-m-d')) }}" required
                                class="date w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Purchase Cost <span class="text-red-500">*</span></label>
                            <input type="number" name="purchase_cost" step="0.01" min="0"
                                value="{{ old('purchase_cost', $asset?->purchase_cost) }}" required
                                {{ $editing ? 'readonly' : '' }}
                                class="w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8] {{ $editing ? 'bg-gray-50' : '' }}">
                            @if ($editing) <p class="text-xs text-gray-400 mt-0.5">Cost cannot be changed after creation</p> @endif
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Residual Value <span class="text-red-500">*</span></label>
                            <input type="number" name="residual_value" step="0.01" min="0"
                                value="{{ old('residual_value', $asset?->residual_value ?? 0) }}" required
                                class="w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Useful Life (months) <span class="text-red-500">*</span></label>
                            <input type="number" name="useful_life_months" min="1"
                                value="{{ old('useful_life_months', $asset?->useful_life_months) }}" required
                                class="w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                        </div>

                        @if ($editing)
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Status <span class="text-red-500">*</span></label>
                            <select name="status" required
                                class="select2 w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                                <option value="active" @selected($asset->status === 'active')>Active</option>
                                <option value="fully_depreciated" @selected($asset->status === 'fully_depreciated')>Fully Depreciated</option>
                                <option value="disposed" @selected($asset->status === 'disposed')>Disposed</option>
                            </select>
                        </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                        <textarea name="description" rows="2"
                            class="w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8] resize-none">{{ old('description', $asset?->description) }}</textarea>
                    </div>
                </div>

                {{-- Accounts --}}
                <div class="bg-white border border-gray-200 rounded-[5px] p-4 space-y-3">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Chart of Account Mapping</h2>
                    @foreach ([
                        ['asset_account_id', 'Asset Account (Balance Sheet)', 'required'],
                        ['depreciation_expense_account_id', 'Depreciation Expense Account', 'required'],
                        ['accumulated_depreciation_account_id', 'Accumulated Depreciation Account', 'required'],
                    ] as [$field, $label, $req])
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $label }} <span class="text-red-500">*</span></label>
                        <select name="{{ $field }}" {{ $req }}
                            class="select2 w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                            <option value="">— Select account —</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}" @selected(old($field, $asset?->{$field}) == $acc->id)>
                                    {{ $acc->name }} ({{ $acc->category }})
                                </option>
                            @endforeach
                        </select>
                        @error($field) <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    @endforeach
                </div>

            </div>{{-- /left --}}

            {{-- Right sidebar --}}
            <div class="space-y-4">
                <div class="bg-white border border-gray-200 rounded-[5px] p-4 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                        <textarea name="notes" rows="4"
                            class="w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8] resize-none">{{ old('notes', $asset?->notes) }}</textarea>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-[#0078C8] text-white text-sm font-semibold py-2.5 rounded-[3px] hover:bg-[#0068b0] transition">
                        {{ $editing ? 'Update Asset' : 'Add to Register' }}
                    </button>
                    <a href="{{ route('account.fixed-asset.index') }}"
                        class="px-4 py-2.5 bg-gray-100 text-gray-700 text-sm rounded-[3px] hover:bg-gray-200">Cancel</a>
                </div>

                <div class="bg-blue-50 border border-blue-100 rounded-[4px] p-3 text-xs text-blue-700">
                    <p class="font-semibold mb-1">Monthly Journal Entry</p>
                    <p>DR Depreciation Expense</p>
                    <p>CR Accumulated Depreciation</p>
                    <p class="mt-1 text-blue-500">Run: <code>php artisan assets:post-depreciation</code></p>
                </div>
            </div>

        </div>
    </form>
</div>
