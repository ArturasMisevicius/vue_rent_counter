<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Vilnius Utilities · Rent Counter</title>

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

    <main class="relative max-w-6xl mx-auto px-6 pb-16 pt-12">
        <div class="grid lg:grid-cols-2 gap-12">
            <div class="space-y-6">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-sky-200 ring-1 ring-white/10">
                        Authentication
                    </p>
                    <h1 class="mt-4 font-display text-4xl sm:text-5xl font-bold text-white leading-tight">
                        Welcome Back
                    </h1>
                    <p class="mt-3 text-lg text-slate-300 leading-relaxed">
                        Sign in to access your utilities dashboard and manage your properties.
                    </p>
                </div>

                <div class="relative rounded-3xl border border-white/10 bg-white/5 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.35)] backdrop-blur">
                    @if ($errors->any())
                        <div class="mb-6 rounded-2xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf
                        <div>
                            <label for="email" class="block text-sm font-semibold text-white mb-2">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                required 
                                autofocus
                                class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50"
                                placeholder="your@email.com"
                            >
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-white mb-2">Password</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50"
                                placeholder="••••••••"
                            >
                        </div>

                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="remember" 
                                name="remember"
                                class="h-4 w-4 rounded border-white/20 bg-white/10 text-indigo-500 focus:ring-2 focus:ring-indigo-500/50"
                            >
                            <label for="remember" class="ml-2 text-sm text-slate-300">Remember me</label>
                        </div>

                        <button 
                            type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-sky-500 px-5 py-3 text-sm font-semibold text-white shadow-glow transition hover:-translate-y-0.5"
                        >
                            Sign In
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400 mb-2">Quick Access</p>
                    <p class="text-sm text-slate-300">
                        <strong class="text-white">Default password:</strong> password
                    </p>
                    <p class="text-xs text-slate-400 mt-2">
                        Click any email in the table to auto-fill the login form
                    </p>
                </div>
            </div>

            <div class="space-y-6" x-data="{ showTable: true }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Available Accounts</p>
                        <h2 class="mt-1 text-2xl font-display font-bold text-white">Test Users</h2>
                    </div>
                    <button 
                        @click="showTable = !showTable"
                        class="inline-flex items-center gap-2 rounded-xl border border-white/15 px-3 py-2 text-xs font-semibold text-white/90 hover:border-white/30"
                    >
                        <span x-text="showTable ? 'Hide' : 'Show'"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="showTable ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <div 
                    x-show="showTable"
                    x-transition
                    class="relative rounded-3xl border border-white/10 bg-white/5 shadow-[0_24px_80px_rgba(0,0,0,0.35)] backdrop-blur overflow-hidden"
                >
                    <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-slate-900/95 backdrop-blur border-b border-white/10">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Role</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-300">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @forelse($users as $user)
                                    <tr 
                                        class="hover:bg-white/5 transition cursor-pointer group"
                                        onclick="document.getElementById('email').value = '{{ $user->email }}'; document.getElementById('email').focus();"
                                    >
                                        <td class="px-4 py-3 text-white font-medium">
                                            {{ $user->name }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-300 group-hover:text-indigo-300 transition">
                                            {{ $user->email }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold
                                                @if($user->role->value === 'superadmin') bg-purple-500/15 text-purple-200 ring-1 ring-purple-500/30
                                                @elseif($user->role->value === 'admin') bg-indigo-500/15 text-indigo-200 ring-1 ring-indigo-500/30
                                                @elseif($user->role->value === 'manager') bg-sky-500/15 text-sky-200 ring-1 ring-sky-500/30
                                                @else bg-slate-500/15 text-slate-200 ring-1 ring-slate-500/30
                                                @endif
                                            ">
                                                {{ ucfirst($user->role->value) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($user->is_active)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-semibold text-emerald-200 ring-1 ring-emerald-500/30">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-red-500/15 px-2.5 py-1 text-xs font-semibold text-red-200 ring-1 ring-red-500/30">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                                            No users found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-white/10 bg-slate-900/50 px-4 py-3">
                        <p class="text-xs text-slate-400">
                            Total users: <span class="font-semibold text-white">{{ $users->count() }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
