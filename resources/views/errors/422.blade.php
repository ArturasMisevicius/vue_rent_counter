<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>422 - Validation Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <div class="text-center">
                <h1 class="text-9xl font-bold text-indigo-600">422</h1>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Validation Error
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    The data you submitted contains errors. Please review and try again.
                </p>
            </div>
            
            @if(isset($errors) && $errors->any())
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Validation Errors:</h3>
                    <ul class="space-y-2">
                        @foreach($errors->all() as $error)
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-red-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm text-gray-700">{{ $error }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <div class="text-center space-y-4">
                <button onclick="history.back()" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Go Back and Fix Errors
                </button>
                
                @auth
                    @php
                        $dashboardRoute = match(auth()->user()->role->value) {
                            'admin' => route('admin.dashboard'),
                            'manager' => route('manager.dashboard'),
                            'tenant' => route('tenant.dashboard'),
                            default => url('/')
                        };
                    @endphp
                    <div>
                        <a href="{{ $dashboardRoute }}" class="text-indigo-600 hover:text-indigo-500">
                            Go to Dashboard
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</body>
</html>
