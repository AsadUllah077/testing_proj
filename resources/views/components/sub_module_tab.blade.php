@props(['name' => null, 'active' => false, 'url' => '#'])

<button onclick="window.location.href='{{ $url }}'"
    class="{{ $active ? 'bg-[#0078C8] border-[#0078C8]' : 'bg-[#eee5e5]  border-[#eee5e5]' }} py-2 px-3 rounded-[3px] border">
    <h1
        class="text-center uppercase justify-start {{ $active ? 'text-white' : 'text-black' }}  text-sm font-normal  uppercase">
        {{ $name }}</h1>
</button>
