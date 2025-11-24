<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Unauthorized</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <h1 class="text-9xl font-bold text-indigo-600">401</h1>
                <h2 class="mt-6 text-3xl font-extrabold text-slate-900">
                    Unauthorized
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    You need to be logged in to access this page.
                </p>
            </div>
            
            <div class="mt-8 space-y-4">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Go to Login
                </a>
                
                <div>
                    <a href="{{ url('/') }}" class="text-indigo-600 hover:text-indigo-500">
                        Return to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
