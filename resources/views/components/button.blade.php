@props(['variant' => 'primary', 'type' => 'button'])

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 border rounded-xl font-semibold text-sm tracking-tight transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 ' . match($variant) {
    'secondary' => 'bg-white/90 text-slate-800 border-slate-200 shadow-sm focus:ring-slate-300',
    'danger' => 'bg-rose-600 text-white border-rose-600 shadow-lg shadow-rose-200/70 focus:ring-rose-400 focus:ring-offset-0',
    default => 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white border-transparent shadow-glow focus:ring-indigo-400 focus:ring-offset-0',
}]) }}>
    {{ $slot }}
</button>
