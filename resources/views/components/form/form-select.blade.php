@props([
    'name' => '',
    'label' => '',
    'placeholder' => 'Select...',
    'options' => [],
    'selected' => '',
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

    <select id="{{ $name }}" name="{{ $name }}" {{ $required ? 'required' : '' }}
        {{ $attributes->merge([
            'class' =>
                'w-full  h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition' .
                ($errors->has($name) ? ' border-red-400' : ''),
        ]) }}
        style="background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E\"); background-size: 1.2rem; background-position: right 0.5rem center;">
        @if ($placeholder)
            <option value="" {{ $selected === '' ? 'selected' : '' }} disabled>{{ $placeholder }}</option>
        @endif

        @foreach ($options as $key => $label)
            <option value="{{ $key }}" {{ $selected == $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    @error($name)
        <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
    @enderror
</div>
