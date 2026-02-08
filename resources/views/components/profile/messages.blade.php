@props([
    'errorTitle',
])

@if(session('success'))
    <x-alert type="success" :dismissible="false">
        {{ session('success') }}
    </x-alert>
@endif

@if($errors->any())
    <x-alert type="error" :dismissible="false">
        <p class="text-sm font-semibold text-rose-800">{{ $errorTitle }}</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
