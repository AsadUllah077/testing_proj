  @props(['url', 'text' => 'Back'])
  <a href="{{ $url }}"
      class="px-6 py-2.5 bg-[#0078C8] border-2 border-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-all duration-200 font-medium text-sm">
      {{ $text }}
  </a>
