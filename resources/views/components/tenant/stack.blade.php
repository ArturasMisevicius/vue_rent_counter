@props(['gap' => '6'])

<div {{ $attributes->merge(['class' => match((string) $gap) {
    '1' => 'space-y-1',
    '2' => 'space-y-2',
    '3' => 'space-y-3',
    '4' => 'space-y-4',
    '5' => 'space-y-5',
    '6' => 'space-y-6',
    '8' => 'space-y-8',
    default => 'space-y-6',
}]) }}>
    {{ $slot }}
</div>
