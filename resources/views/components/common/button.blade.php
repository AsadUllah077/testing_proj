@props(['url', 'text' => 'Cancel', 'type' => 'button', 'class' => ''])
<div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
    <a href="{{ $url }}"
        class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 font-medium text-sm">
        {{ $text }}
    </a>
    <button type="{{ $type }}"
        class="px-6 py-2.5 bg-[#0078C8] border-2 border-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-all duration-200 font-medium text-sm">
        Save Record
    </button>
</div>
