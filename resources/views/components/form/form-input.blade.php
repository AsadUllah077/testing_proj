@props([
    'name' => '',
    'label' => '',
    'type' => 'text',
    'placeholder' => '',
    'value' => '',
    'required' => false,
])

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $name }}" class="text-slate-700 text-sm font-semibold leading-5">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" value="{{ $value }}"
        placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}
        {{ $attributes->merge([
            'class' =>
                'w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition' .
                ($errors->has($name) ? ' border-red-400' : ''),
        ]) }}>

    @error($name)
        <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
    @enderror
</div>
