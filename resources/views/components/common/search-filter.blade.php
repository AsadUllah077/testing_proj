@props(['name', 'id', 'value', 'label'])
<div class="flex-1 min-w-[150px]">
    <label class="block text-xs font-medium text-gray-600 mb-2">{{ $label }}</label>
    <input type="text" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}"
        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
</div>
