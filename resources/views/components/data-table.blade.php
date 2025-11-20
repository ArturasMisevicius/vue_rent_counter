@props(['header' => null])

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-gray-200']) }}>
        @if($header)
            <thead class="bg-gray-50">
                {{ $header }}
            </thead>
        @endif
        <tbody class="bg-white divide-y divide-gray-200">
            {{ $slot }}
        </tbody>
    </table>
</div>
