@props(['name', 'label', 'type' => 'text', 'value' => '', 'required' => false])

<div class="ds-field space-y-2">
    <label for="{{ $name }}" class="ds-field__label block text-sm font-medium text-slate-700">
        {{ $label }}
        @if($required)
            <span class="ds-field__required ml-1 text-rose-600">*</span>
        @endif
    </label>

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->class([
            'ds-input block w-full rounded-xl border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20',
            'ds-input--error border-rose-300 focus:border-rose-500 focus:ring-rose-500/20' => $errors->has($name),
            'border-slate-300' => ! $errors->has($name),
        ]) }}
    >

    @error($name)
        <p class="ds-field__error text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>
