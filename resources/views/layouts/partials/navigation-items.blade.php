@foreach($navigationLinks as $link)
    <a href="{{ route($link['route']) }}" class="{{ str_starts_with($currentRoute, $link['prefix']) ? (($mobile ?? false) ? $mobileActiveClass : $activeClass) : (($mobile ?? false) ? $mobileInactiveClass : $inactiveClass) }} {{ ($mobile ?? false) ? 'block rounded-lg px-3 py-2 text-base font-semibold' : 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold transition' }}">
        {{ $link['label'] }}
    </a>
@endforeach
