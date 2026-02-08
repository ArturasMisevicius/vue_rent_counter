@props(['name', 'label', 'options' => [], 'value' => null, 'selected' => null, 'required' => false, 'placeholder' => null])

<div class="space-y-2">
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">
        {{ $label }}
        @if($required)
            <span class="ml-1 text-rose-600">*</span>
        @endif
    </label>

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->class([
            'block w-full rounded-xl border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20',
            'border-rose-300 focus:border-rose-500 focus:ring-rose-500/20' => $errors->has($name),
            'border-slate-300' => ! $errors->has($name),
        ]) }}
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
        <p class="text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>
