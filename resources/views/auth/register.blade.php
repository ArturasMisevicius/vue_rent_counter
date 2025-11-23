<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Vilnius Utilities · Rent Counter</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Manrope"', 'system-ui', 'sans-serif'],
                        display: ['"Space Grotesk"', '"Manrope"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        midnight: '#0f172a',
                        skyline: '#38bdf8',
                        indigoInk: '#6366f1',
                    },
                    boxShadow: {
                        glow: '0 18px 50px rgba(99, 102, 241, 0.2)',
                    },
                },
            },
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-950 text-slate-50 antialiased">

<div class="relative overflow-hidden min-h-screen">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-24 -top-32 h-80 w-80 rounded-full bg-indigo-500/30 blur-[120px]"></div>
        <div class="absolute right-0 top-10 h-72 w-72 rounded-full bg-sky-400/25 blur-[110px]"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-slate-900 via-slate-950 to-slate-950"></div>
    </div>

    <header class="relative max-w-6xl mx-auto px-6 pt-10 flex items-center justify-between">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-400 text-white font-display text-xl shadow-glow">V</span>
            <div class="leading-tight">
                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-300">Vilnius Utilities</p>
                <p class="font-display text-lg text-white">Rent Counter</p>
            </div>
        </a>

        <a href="{{ url('/') }}" class="text-sm font-semibold text-slate-200 hover:text-white">
            ← Back to Home
        </a>
    </header>

    <main class="relative max-w-4xl mx-auto px-6 pb-16 pt-12">
        <div class="space-y-8">
            <!-- Welcome Section -->
            <div class="text-center">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-sky-200 ring-1 ring-white/10">
                    Create Account
                </p>
                <h1 class="mt-4 font-display text-4xl sm:text-5xl font-bold text-white leading-tight">
                    Get Started
                </h1>
                <p class="mt-3 text-lg text-slate-300 leading-relaxed max-w-2xl mx-auto">
                    Create a new tenant account to access your utilities dashboard and manage your properties.
                </p>
            </div>

            <!-- Register Form Card -->
            <div class="relative rounded-3xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-8 shadow-[0_24px_80px_rgba(0,0,0,0.35)] backdrop-blur-sm">
                <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-indigo-500/10 via-transparent to-sky-500/10 opacity-50"></div>
                
                <div class="relative">
                    @if ($errors->any())
                        <div class="mb-6 rounded-2xl border border-red-500/30 bg-red-500/10 backdrop-blur px-4 py-3 text-sm text-red-200 shadow-lg">
                            @foreach ($errors->all() as $error)
                                <p class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $error }}
                                </p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}" class="space-y-6">
                        @csrf
                        <div>
                            <label for="name" class="block text-sm font-semibold text-white mb-2.5">Full Name</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="{{ old('name') }}" 
                                required 
                                autofocus
                                class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3.5 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all duration-200 hover:bg-white/15"
                                placeholder="John Doe"
                            >
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-white mb-2.5">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                required
                                class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3.5 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all duration-200 hover:bg-white/15"
                                placeholder="your@email.com"
                            >
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="password" class="block text-sm font-semibold text-white mb-2.5">Password</label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    required
                                    class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3.5 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all duration-200 hover:bg-white/15"
                                    placeholder="••••••••"
                                >
                                <p class="mt-1.5 text-xs text-slate-400">Minimum 8 characters</p>
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-semibold text-white mb-2.5">Confirm Password</label>
                                <input 
                                    type="password" 
                                    id="password_confirmation" 
                                    name="password_confirmation" 
                                    required
                                    class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3.5 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all duration-200 hover:bg-white/15"
                                    placeholder="••••••••"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="tenant_id" class="block text-sm font-semibold text-white mb-2.5">Tenant ID</label>
                            <input 
                                type="number" 
                                id="tenant_id" 
                                name="tenant_id" 
                                value="{{ old('tenant_id') }}" 
                                required
                                class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3.5 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all duration-200 hover:bg-white/15"
                                placeholder="1"
                            >
                            <p class="mt-1.5 text-xs text-slate-400">Enter your organization's tenant ID</p>
                        </div>

                        <button 
                            type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 via-indigo-600 to-sky-500 px-6 py-3.5 text-sm font-semibold text-white shadow-glow transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_20px_60px_rgba(99,102,241,0.4)] active:translate-y-0"
                        >
                            Create Account
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Login Link -->
            <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur text-center">
                <p class="text-sm text-slate-300 mb-4">
                    Already have an account?
                </p>
                <a 
                    href="{{ route('login') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/5 px-5 py-2.5 text-sm font-semibold text-white hover:border-white/30 hover:bg-white/10 transition-all duration-200"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Sign In Instead
                </a>
            </div>
        </div>
    </main>
</div>

</body>
</html>
