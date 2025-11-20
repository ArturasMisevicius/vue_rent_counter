@props(['label', 'value', 'icon' => null])

<div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
        <div class="flex items-center">
            @if($icon)
                <div class="flex-shrink-0">
                    {{ $icon }}
                </div>
            @endif
            <div class="{{ $icon ? 'ml-5' : '' }} w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        {{ $label }}
                    </dt>
                    <dd class="text-3xl font-semibold text-gray-900">
                        {{ $value }}
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
