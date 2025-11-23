@props(['variant' => 'primary', 'type' => 'button'])

@php
$classes = match($variant) {
    'primary' => 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white border-transparent shadow-glow hover:from-indigo-600 hover:to-sky-500 focus:ring-indigo-400 focus:ring-offset-0',
    'secondary' => 'bg-white/90 text-slate-800 border-slate-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 focus:ring-slate-300',
    'danger' => 'bg-rose-600 text-white border-rose-600 shadow-lg shadow-rose-200/70 hover:bg-rose-700 focus:ring-rose-400 focus:ring-offset-0',
    default => 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white border-transparent shadow-glow hover:from-indigo-600 hover:to-sky-500 focus:ring-indigo-400 focus:ring-offset-0',
};
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 px-4 py-2.5 border rounded-xl font-semibold text-sm tracking-tight transition transform duration-150 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 $classes"]) }}>
    {{ $slot }}
</button>
