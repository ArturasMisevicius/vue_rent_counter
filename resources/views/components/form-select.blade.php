@props(['name', 'label', 'options' => [], 'value' => null, 'selected' => null, 'required' => false, 'placeholder' => null])

@php($selectedValue = old($name, $value ?? $selected ?? ''))

<div class="mb-4">
    <label for="{{ $name }}" class="block text-sm font-semibold text-slate-700 mb-1 flex items-center gap-1">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    
    <select 
        name="{{ $name }}" 
        id="{{ $name }}" 
        {{ $required ? 'required' : '' }}
        @class([
            'mt-1 block w-full rounded-xl shadow-sm focus:ring-2 sm:text-sm transition bg-white/90',
            'border border-red-300 text-red-900 focus:border-red-400 focus:ring-red-300' => $errors->has($name),
            'border border-slate-200 text-slate-900 focus:border-indigo-300 focus:ring-indigo-200' => !$errors->has($name),
        ])
        {{ $attributes->except('class') }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" {{ (string) $selectedValue === (string) $optionValue ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
    
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
