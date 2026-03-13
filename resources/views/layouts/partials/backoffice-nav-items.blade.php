@foreach($backofficeLinks as $link)
    <a href="{{ route($link['route']) }}" class="{{ str_starts_with($currentRoute, $link['prefix']) ? (($mobile ?? false) ? $mobileActiveClass : $activeClass) : (($mobile ?? false) ? $mobileInactiveClass : $inactiveClass) }} {{ ($mobile ?? false) ? 'block px-3 py-2 rounded-lg text-base font-semibold' : 'px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition' }}">
        {{ $link['label'] }}
    </a>
@endforeach
