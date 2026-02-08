@props(['name', 'label', 'type' => 'text', 'value' => '', 'required' => false])

<div class="ds-field">
    <label for="{{ $name }}" class="ds-field__label">
        {{ $label }}
        @if($required)
            <span class="ds-field__required">*</span>
        @endif
    </label>

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        {{ $required ? 'required' : '' }}
        @class([
            'ds-input',
            'ds-input--error' => $errors->has($name),
        ])
        {{ $attributes->except('class') }}
    >

    @error($name)
        <p class="ds-field__error">{{ $message }}</p>
    @enderror
</div>
