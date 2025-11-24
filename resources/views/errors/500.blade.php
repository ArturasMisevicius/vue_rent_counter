<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <h1 class="text-9xl font-bold text-indigo-600">500</h1>
                <h2 class="mt-6 text-3xl font-extrabold text-slate-900">
                    Server Error
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    Something went wrong on our end. We're working to fix it.
                </p>
            </div>
            
            <div class="bg-white shadow rounded-lg p-6 text-left">
                <h3 class="text-sm font-medium text-slate-900 mb-2">What you can do:</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-indigo-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Try refreshing the page
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-indigo-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Go back and try again
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-indigo-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Contact support if the problem persists
                    </li>
                </ul>
            </div>
            
            <div class="mt-8 space-y-4">
                <button onclick="location.reload()" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Refresh Page
                </button>
                
                <div class="flex justify-center space-x-4">
                    <button onclick="history.back()" class="text-indigo-600 hover:text-indigo-500">
                        Go Back
                    </button>
                    
                    @auth
                        <span class="text-slate-300">|</span>
                        @if(auth()->user()->role->value === 'admin')
                            <a href="{{ route('filament.admin.pages.dashboard') }}" class="text-indigo-600 hover:text-indigo-500">
                                Go to Dashboard
                            </a>
                        @elseif(auth()->user()->role->value === 'manager')
                            <a href="{{ route('manager.dashboard') }}" class="text-indigo-600 hover:text-indigo-500">
                                Go to Dashboard
                            </a>
                        @elseif(auth()->user()->role->value === 'tenant')
                            <a href="{{ route('tenant.dashboard') }}" class="text-indigo-600 hover:text-indigo-500">
                                Go to Dashboard
                            </a>
                        @else
                            <a href="{{ url('/') }}" class="text-indigo-600 hover:text-indigo-500">
                                Go to Home
                            </a>
                        @endif
                    @else
                        <span class="text-slate-300">|</span>
                        <a href="{{ url('/') }}" class="text-indigo-600 hover:text-indigo-500">
                            Go to Home
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</body>
</html>
