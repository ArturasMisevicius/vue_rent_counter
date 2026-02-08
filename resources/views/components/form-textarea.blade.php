@props(['name', 'label', 'value' => '', 'required' => false, 'rows' => 3, 'placeholder' => ''])

<div class="ds-field">
    <label for="{{ $name }}" class="ds-field__label">
        {{ $label }}
        @if($required)
            <span class="ds-field__required">*</span>
        @endif
    </label>

    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        {{ $required ? 'required' : '' }}
        @class([
            'ds-textarea',
            'ds-textarea--error' => $errors->has($name),
        ])
        placeholder="{{ $placeholder }}"
        {{ $attributes->except('class') }}
    >{{ old($name, $value) }}</textarea>

    @error($name)
        <p class="ds-field__error">{{ $message }}</p>
    @enderror
</div>
