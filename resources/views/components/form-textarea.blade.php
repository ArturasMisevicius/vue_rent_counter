@props(['name', 'label', 'value' => '', 'required' => false, 'rows' => 3, 'placeholder' => ''])

<div class="mb-4">
    <label for="{{ $name }}" class="block text-sm font-semibold text-slate-700 mb-1 flex items-center gap-1">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    
    <textarea 
        name="{{ $name }}" 
        id="{{ $name }}" 
        rows="{{ $rows }}"
        {{ $required ? 'required' : '' }}
        @class([
            'mt-1 block w-full rounded-xl shadow-sm focus:ring-2 sm:text-sm transition bg-white/90',
            'border border-red-300 text-red-900 placeholder-red-300 focus:border-red-400 focus:ring-red-300' => $errors->has($name),
            'border border-slate-200 text-slate-900 placeholder-slate-400 focus:border-indigo-300 focus:ring-indigo-200' => !$errors->has($name),
        ])
        placeholder="{{ $placeholder }}"
        {{ $attributes->except('class') }}
    >{{ old($name, $value) }}</textarea>
    
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
