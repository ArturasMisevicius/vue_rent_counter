@props(['name', 'label', 'options' => [], 'value' => '', 'required' => false, 'placeholder' => null])

<div class="mb-4">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
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
            'mt-1 block w-full rounded-md shadow-sm focus:ring-indigo-500 sm:text-sm',
            'border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500' => $errors->has($name),
            'border-gray-300 focus:border-indigo-500' => !$errors->has($name),
        ])
        {{ $attributes->except('class') }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" {{ old($name, $value) == $optionValue ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
    
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
