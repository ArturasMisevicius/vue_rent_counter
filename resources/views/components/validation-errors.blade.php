@if($errors->any())
    <x-alert type="error" :dismissible="false" class="mb-6">
        <div>
            <h3 class="font-semibold mb-2">{{ __('validation.errors_occurred') }}</h3>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </x-alert>
@endif
