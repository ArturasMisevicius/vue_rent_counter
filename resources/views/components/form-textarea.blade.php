@props(['name', 'label', 'value' => '', 'required' => false, 'rows' => 3, 'placeholder' => ''])

<div class="space-y-2">
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">
        {{ $label }}
        @if($required)
            <span class="ml-1 text-rose-600">*</span>
        @endif
    </label>

    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->class([
            'block w-full rounded-xl border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20',
            'border-rose-300 focus:border-rose-500 focus:ring-rose-500/20' => $errors->has($name),
            'border-slate-300' => ! $errors->has($name),
        ]) }}
        placeholder="{{ $placeholder }}"
    >{{ old($name, $value) }}</textarea>

    @error($name)
        <p class="text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>
