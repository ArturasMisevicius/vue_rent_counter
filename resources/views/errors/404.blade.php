<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Not Found</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <h1 class="text-9xl font-bold text-indigo-600">404</h1>
                <h2 class="mt-6 text-3xl font-extrabold text-slate-900">
                    Page Not Found
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    The page you're looking for doesn't exist or has been moved.
                </p>
            </div>
            
            <div class="mt-8 space-y-4">
                @auth
                    @if(auth()->user()->role->value === 'admin')
                        <a href="{{ route('filament.admin.pages.dashboard') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Go to Dashboard
                        </a>
                    @elseif(auth()->user()->role->value === 'manager')
                        <a href="{{ route('manager.dashboard') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Go to Dashboard
                        </a>
                    @elseif(auth()->user()->role->value === 'tenant')
                        <a href="{{ route('tenant.dashboard') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Go to Home
                        </a>
                    @endif
                @else
                    <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        Go to Home
                    </a>
                @endauth
                
                <div>
                    <button onclick="history.back()" class="text-indigo-600 hover:text-indigo-500">
                        Go Back
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
