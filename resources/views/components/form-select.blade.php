@props(['name', 'label', 'options' => [], 'value' => null, 'selected' => null, 'required' => false, 'placeholder' => null])

<div class="ds-field">
    <label for="{{ $name }}" class="ds-field__label">
        {{ $label }}
        @if($required)
            <span class="ds-field__required">*</span>
        @endif
    </label>

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $required ? 'required' : '' }}
        @class([
            'ds-select',
            'ds-select--error' => $errors->has($name),
        ])
        {{ $attributes->except('class') }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" {{ (string) old($name, $value ?? $selected ?? '') === (string) $optionValue ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @error($name)
        <p class="ds-field__error">{{ $message }}</p>
    @enderror
</div>
