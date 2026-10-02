@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'options' => [],
    'multiple' => false,
])

@php
    $current = old($name, $value);
    $selectedValues = array_map('strval', (array) $current);
@endphp

<div>
    <label for="{{ $name }}" class="mb-2 block font-semibold">
        {{ $label }}
    </label>

    @if ($type === 'checkbox')
        <input type="hidden" name="{{ $name }}" value="0">

        <input
            id="{{ $name }}"
            type="checkbox"
            name="{{ $name }}"
            value="1"
            @checked((bool) $current)
        >
    @elseif ($type === 'select')
        @if ($multiple)
            {{-- Ensures clearing every selection submits an empty value. --}}
            <input type="hidden" name="{{ $name }}" value="">
        @endif

        <select
            id="{{ $name }}"
            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
            @if ($multiple) multiple size="6" @endif
            {{ $attributes->class([
                'w-full rounded-lg border border-gray-300 bg-white p-3',
            ]) }}
        >
            @unless ($multiple)
                <option value="">Select an option</option>
            @endunless

            @foreach ($options as $optionValue => $optionLabel)
                <option
                    value="{{ $optionValue }}"
                    @selected(in_array(
                        (string) $optionValue,
                        $selectedValues,
                        true
                    ))
                >
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="6"
            {{ $attributes->class([
                'w-full rounded-lg border border-gray-300 p-3',
            ]) }}
        >{{ $current }}</textarea>
    @else
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $type === 'password' ? '' : $current }}"
            {{ $attributes->class([
                'w-full rounded-lg border border-gray-300 p-3',
            ]) }}
        >
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>