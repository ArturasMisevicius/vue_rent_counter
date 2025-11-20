@props(['variant' => 'primary', 'type' => 'button'])

@php
$classes = match($variant) {
    'primary' => 'bg-indigo-600 hover:bg-indigo-700 text-white',
    'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-800',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white',
    default => 'bg-indigo-600 hover:bg-indigo-700 text-white',
};
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 $classes"]) }}>
    {{ $slot }}
</button>
