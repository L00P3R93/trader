<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CopyTrade Pro — Copy the Best Traders. Automatically.</title>
    <meta name="description" content="Connect your Deriv account, follow top traders, and let CopyTrade Pro mirror every trade in real time.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-14px); }
        }
        @keyframes float2 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(0.5deg); }
        }
        @keyframes orb1 {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.4; }
            33% { transform: translate(60px, -80px) scale(1.3); opacity: 0.6; }
            66% { transform: translate(-40px, 40px) scale(0.8); opacity: 0.25; }
        }
        @keyframes orb2 {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
            33% { transform: translate(-70px, 50px) scale(1.2); opacity: 0.45; }
            66% { transform: translate(50px, -30px) scale(0.9); opacity: 0.2; }
        }
        @keyframes orb3 {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.2; }
            50% { transform: translate(30px, 50px) scale(1.2); opacity: 0.35; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes tradeIn {
            from { opacity: 0; transform: translateX(-12px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.5); }
        }
        @keyframes glowPulse {
            0%, 100% { box-shadow: 0 0 20px rgba(34,197,94,0.15); }
            50% { box-shadow: 0 0 40px rgba(34,197,94,0.35); }
        }
        .animate-float  { animation: float 7s ease-in-out infinite; }
        .animate-float2 { animation: float2 9s ease-in-out infinite 1s; }
        .orb-1 { animation: orb1 28s ease-in-out infinite; }
        .orb-2 { animation: orb2 35s ease-in-out infinite 5s; }
        .orb-3 { animation: orb3 22s ease-in-out infinite 10s; }
        .slide-up-1 { animation: slideUp 0.8s ease-out 0.1s both; }
        .slide-up-2 { animation: slideUp 0.8s ease-out 0.25s both; }
        .slide-up-3 { animation: slideUp 0.8s ease-out 0.4s both; }
        .slide-up-4 { animation: slideUp 0.8s ease-out 0.6s both; }
        .slide-up-5 { animation: slideUp 0.8s ease-out 0.75s both; }
        .trade-1 { animation: tradeIn 0.4s ease-out 0.4s both; }
        .trade-2 { animation: tradeIn 0.4s ease-out 0.8s both; }
        .trade-3 { animation: tradeIn 0.4s ease-out 1.2s both; }
        .trade-4 { animation: tradeIn 0.4s ease-out 1.6s both; }
        .trade-5 { animation: tradeIn 0.4s ease-out 2.0s both; }
        .live-pulse { animation: pulseDot 1.8s ease-in-out infinite; }
        .glow-pulse  { animation: glowPulse 3s ease-in-out infinite; }
        .glass-card {
            background: rgba(24, 24, 27, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.07);
        }
    </style>
</head>
<body class="bg-[#0a0a0a] text-white antialiased">

{{-- ═══════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════ --}}
<nav
    x-data="{ open: false, scrolled: false }"
    @scroll.window="scrolled = window.scrollY > 20"
    :class="scrolled ? 'bg-[#0a0a0a]/90 border-b border-zinc-800/60' : 'bg-transparent border-b border-transparent'"
    class="fixed top-0 inset-x-0 z-50 transition-all duration-300 backdrop-blur-md"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2.5 flex-shrink-0">
                <div class="w-8 h-8 rounded-lg bg-green-500 flex items-center justify-center shadow-lg shadow-green-500/30">
                    <svg class="w-4 h-4 text-black" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                    </svg>
                </div>
                <span class="text-base font-bold tracking-tight">CopyTrade Pro</span>
            </a>

            {{-- Desktop nav links --}}
            <div class="hidden md:flex items-center gap-7">
                <a href="#about"        class="text-sm text-zinc-400 hover:text-white transition-colors">About</a>
                <a href="#services"     class="text-sm text-zinc-400 hover:text-white transition-colors">Services</a>
                <a href="#how-it-works" class="text-sm text-zinc-400 hover:text-white transition-colors">How It Works</a>
                <a href="#faq"          class="text-sm text-zinc-400 hover:text-white transition-colors">FAQ</a>
            </div>

            {{-- Desktop auth buttons --}}
            <div class="hidden md:flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg bg-green-500 text-black text-sm font-bold hover:bg-green-400 transition-colors">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg border border-zinc-700 text-sm text-zinc-300 hover:text-white hover:border-zinc-500 transition-colors">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg bg-green-500 text-black text-sm font-bold hover:bg-green-400 transition-colors shadow-lg shadow-green-500/20">
                        Get Started Free
                    </a>
                @endauth
            </div>

            {{-- Mobile hamburger --}}
            <button @click="open = !open" class="md:hidden p-2 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors">
                <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Mobile drawer --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="md:hidden border-t border-zinc-800 py-4 space-y-1"
        >
            <a href="#about"        @click="open=false" class="block px-3 py-2.5 text-sm text-zinc-400 hover:text-white rounded-lg hover:bg-zinc-800/50 transition-colors">About</a>
            <a href="#services"     @click="open=false" class="block px-3 py-2.5 text-sm text-zinc-400 hover:text-white rounded-lg hover:bg-zinc-800/50 transition-colors">Services</a>
            <a href="#how-it-works" @click="open=false" class="block px-3 py-2.5 text-sm text-zinc-400 hover:text-white rounded-lg hover:bg-zinc-800/50 transition-colors">How It Works</a>
            <a href="#faq"          @click="open=false" class="block px-3 py-2.5 text-sm text-zinc-400 hover:text-white rounded-lg hover:bg-zinc-800/50 transition-colors">FAQ</a>
            <div class="flex flex-col gap-2 pt-3 mt-2 border-t border-zinc-800">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-3 rounded-lg bg-green-500 text-black text-sm font-bold text-center">Dashboard</a>
                @else
                    <a href="{{ route('login') }}"    class="px-4 py-3 rounded-lg border border-zinc-700 text-sm text-zinc-300 text-center">Login</a>
                    <a href="{{ route('register') }}" class="px-4 py-3 rounded-lg bg-green-500 text-black text-sm font-bold text-center">Get Started Free</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

{{-- ═══════════════════════════════════════════
     HERO SECTION
═══════════════════════════════════════════ --}}
<section class="relative min-h-screen flex items-center overflow-hidden bg-[#0a0a0a]">

    {{-- Gradient orbs --}}
    <div class="orb-1 absolute top-1/4 left-1/3 w-[500px] h-[500px] rounded-full bg-green-500/20 blur-[120px] pointer-events-none"></div>
    <div class="orb-2 absolute bottom-1/3 right-1/4 w-[400px] h-[400px] rounded-full bg-blue-500/15 blur-[100px] pointer-events-none"></div>
    <div class="orb-3 absolute top-2/3 left-1/2 w-[300px] h-[300px] rounded-full bg-green-400/10 blur-[80px] pointer-events-none"></div>

    {{-- Subtle grid --}}
    <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.015)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.015)_1px,transparent_1px)] bg-[size:64px_64px] pointer-events-none"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-28 w-full">
        <div class="grid lg:grid-cols-2 gap-12 xl:gap-20 items-center">

            {{-- Left: Content --}}
            <div class="text-center lg:text-left">

                {{-- Live badge --}}
                <div class="slide-up-1 inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-green-500/10 border border-green-500/25 text-green-400 text-xs font-semibold mb-7">
                    <span class="live-pulse w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span>
                    Live on Deriv WebSocket API
                </div>

                {{-- Headline --}}
                <h1 class="slide-up-2 text-4xl sm:text-5xl lg:text-6xl xl:text-[4.5rem] font-extrabold tracking-tight leading-[1.1] mb-6">
                    Copy the Best<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-green-400 via-green-300 to-emerald-400">Traders.</span><br>
                    Automatically.
                </h1>

                {{-- Subheadline --}}
                <p class="slide-up-3 text-lg sm:text-xl text-zinc-400 leading-relaxed max-w-lg mx-auto lg:mx-0 mb-9">
                    Connect your Deriv account, follow top traders, and let our platform mirror every trade in real time — while you earn.
                </p>

                {{-- CTA buttons --}}
                <div class="slide-up-4 flex flex-col sm:flex-row gap-3 justify-center lg:justify-start mb-10">
                    @guest
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-green-500 text-black font-bold text-base hover:bg-green-400 transition-all hover:scale-[1.03] active:scale-95 shadow-lg shadow-green-500/30">
                            Start Copying Trades
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-green-500 text-black font-bold text-base hover:bg-green-400 transition-all hover:scale-[1.03] shadow-lg shadow-green-500/30">
                            Go to Dashboard
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    @endauth
                    <a href="#how-it-works" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl border border-zinc-700 text-white font-semibold text-base hover:bg-zinc-800/60 transition-all">
                        See How It Works
                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                </div>

                {{-- Trust badges --}}
                <div class="slide-up-5 flex flex-wrap items-center justify-center lg:justify-start gap-x-5 gap-y-2 text-xs text-zinc-500">
                    <span>🔒 Deriv API Secured</span>
                    <span class="hidden sm:block w-px h-3 bg-zinc-700"></span>
                    <span>⚡ Real-Time Execution</span>
                    <span class="hidden sm:block w-px h-3 bg-zinc-700"></span>
                    <span>📊 Live P&L Tracking</span>
                    <span class="hidden sm:block w-px h-3 bg-zinc-700"></span>
                    <span>💰 Earn While You Sleep</span>
                </div>
            </div>

            {{-- Right: Floating mockup cards --}}
            <div class="hidden lg:flex items-center justify-center relative h-[480px]">

                {{-- Balance card --}}
                <div class="animate-float absolute top-4 right-4 w-72 glass-card rounded-2xl p-5 shadow-2xl glow-pulse z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-[11px] text-zinc-500 mb-1 uppercase tracking-wide">Total Balance</p>
                            <p class="text-2xl font-extrabold text-white tabular-nums">$1,247.50</p>
                        </div>
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-500/15 border border-green-500/25">
                            <span class="live-pulse w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span>
                            <span class="text-green-400 text-[11px] font-semibold">Live</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-green-400 font-semibold text-sm">+$47.20 today</span>
                        <span class="text-zinc-700">·</span>
                        <span class="text-zinc-500 text-xs">3 accounts live</span>
                    </div>
                    <div class="h-1 bg-zinc-800 rounded-full overflow-hidden">
                        <div class="h-full w-[73%] bg-gradient-to-r from-green-600 to-green-400 rounded-full"></div>
                    </div>
                </div>

                {{-- Trade feed card --}}
                <div class="animate-float2 absolute bottom-4 left-0 w-[300px] glass-card rounded-2xl p-5 shadow-2xl z-10">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs font-bold text-white tracking-wide">Live Trade Feed</p>
                        <div class="flex items-center gap-1.5 text-[11px] text-zinc-500">
                            <span class="live-pulse w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span>
                            Real-time
                        </div>
                    </div>
                    <div class="space-y-2.5">
                        <div class="trade-1 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-500/20 text-green-400">CALL</span>
                                <span class="text-xs text-zinc-300 font-mono">R_100</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-zinc-500 tabular-nums">$2.50</span>
                                <span class="text-[11px] font-semibold text-green-400">✓ Won</span>
                            </div>
                        </div>
                        <div class="trade-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-500/20 text-red-400">PUT</span>
                                <span class="text-xs text-zinc-300 font-mono">EURUSD</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-zinc-500 tabular-nums">$1.00</span>
                                <span class="text-[11px] font-semibold text-red-400">✗ Lost</span>
                            </div>
                        </div>
                        <div class="trade-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-500/20 text-green-400">CALL</span>
                                <span class="text-xs text-zinc-300 font-mono">R_50</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-zinc-500 tabular-nums">$5.00</span>
                                <span class="text-[11px] font-semibold text-green-400">✓ Won</span>
                            </div>
                        </div>
                        <div class="trade-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-500/20 text-red-400">PUT</span>
                                <span class="text-xs text-zinc-300 font-mono">R_100</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-zinc-500 tabular-nums">$2.00</span>
                                <span class="text-[11px] font-semibold text-green-400">✓ Won</span>
                            </div>
                        </div>
                        <div class="trade-5 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-500/20 text-green-400">CALL</span>
                                <span class="text-xs text-zinc-300 font-mono">GBPUSD</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-zinc-500 tabular-nums">$3.00</span>
                                <span class="text-[11px] font-semibold text-yellow-400 animate-pulse">● Pending</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Background ambient glow --}}
                <div class="absolute inset-8 bg-green-500/5 rounded-3xl blur-2xl pointer-events-none"></div>
            </div>
        </div>
    </div>

    {{-- Bottom fade --}}
    <div class="absolute bottom-0 inset-x-0 h-28 bg-gradient-to-t from-[#0a0a0a] to-transparent pointer-events-none"></div>
</section>

{{-- ═══════════════════════════════════════════
     STATS BAR
═══════════════════════════════════════════ --}}
<section class="border-y border-zinc-800/50 bg-[#060606]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-10">
            @foreach ([
                ['500+',  'Active Traders'],
                ['$2.4M+','Volume Copied'],
                ['98%',   'Platform Uptime'],
                ['< 3s',  'Avg Copy Speed'],
            ] as [$num, $label])
            <div class="text-center">
                <p class="text-3xl lg:text-4xl font-extrabold text-green-400 mb-1.5 tabular-nums">{{ $num }}</p>
                <p class="text-sm text-zinc-500">{{ $label }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     ABOUT SECTION
═══════════════════════════════════════════ --}}
<section id="about" class="py-24 bg-[#0a0a0a]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <p class="text-green-400 text-xs font-bold uppercase tracking-widest mb-3">About</p>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">What is CopyTrade Pro?</h2>
        </div>
        <div class="grid lg:grid-cols-2 gap-12 xl:gap-20 items-start">
            <div class="space-y-5 text-zinc-400 text-base leading-relaxed">
                <p>
                    CopyTrade Pro is a fully automated copy trading platform built directly on the official
                    <span class="text-white font-semibold">Deriv WebSocket API</span>.
                    No third-party bridges. No hacks. Pure, persistent WebSocket connections straight to Deriv's infrastructure.
                </p>
                <p>
                    Master traders connect their Deriv accounts via API token. Our system maintains a live connection,
                    monitoring every trade they place. The moment a master executes a trade, our platform fans it out
                    to all active followers — typically within 3 seconds.
                </p>
                <p>
                    Your funds <span class="text-white font-semibold">never leave your Deriv account</span>.
                    We never hold money, never execute trades on pooled accounts, and never touch your funds directly.
                    Every trade is placed in your personal account via your own API token.
                </p>
                <p>
                    API tokens are stored with AES-256 encryption using Laravel's built-in encrypted cast.
                    Even if our database were compromised, your tokens would be completely unreadable.
                </p>
            </div>
            <div class="space-y-3">
                @foreach ([
                    'Real-time trade mirroring via Deriv WebSocket API',
                    'Military-grade encrypted API token storage',
                    'Your funds stay in YOUR Deriv account always',
                    'Proportional, fixed, or multiplier stake modes',
                    'Daily loss limits to protect your capital',
                    'Pause or stop copying any time, instantly',
                ] as $feature)
                <div class="flex items-center gap-3 p-4 rounded-xl bg-zinc-900/60 border border-zinc-800 hover:border-green-500/30 transition-colors">
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-green-500/15 border border-green-500/30 flex items-center justify-center">
                        <svg class="w-3 h-3 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="text-sm text-zinc-300">{{ $feature }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     SERVICES SECTION
═══════════════════════════════════════════ --}}
<section id="services" class="py-24 bg-[#070707]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <p class="text-green-400 text-xs font-bold uppercase tracking-widest mb-3">Services</p>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">Everything You Need to Trade Smarter</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ([
                [
                    'title' => 'Real-Time Copy Trading',
                    'desc'  => 'Every trade your chosen master places is instantly mirrored to your Deriv account via a live, persistent WebSocket connection.',
                    'icon'  => 'M13 10V3L4 14h7v7l9-11h-7z',
                ],
                [
                    'title' => 'Smart Risk Management',
                    'desc'  => 'Set daily loss limits, choose stake scaling modes, and pause copying any time. Your risk, your rules — always.',
                    'icon'  => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                ],
                [
                    'title' => 'Live P&L Dashboard',
                    'desc'  => 'Track your performance with real-time charts, live balance updates, and a streaming trade feed — all without a page refresh.',
                    'icon'  => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                ],
                [
                    'title' => 'Master Leaderboard',
                    'desc'  => 'Browse and compare top-performing master traders by win rate, total P&L, and trading history before you commit to following.',
                    'icon'  => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
                ],
                [
                    'title' => 'Multi-Account Support',
                    'desc'  => 'Connect multiple Deriv accounts and follow different masters with completely separate risk settings and copy modes per account.',
                    'icon'  => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                ],
                [
                    'title' => 'Deriv API Powered',
                    'desc'  => 'Built directly on the official Deriv WebSocket API. No third-party bridges, no MT4 tricks — pure, fast, and officially supported.',
                    'icon'  => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                ],
            ] as $card)
            <div class="group p-6 rounded-2xl bg-zinc-900 border border-zinc-800 hover:border-green-500/30 hover:shadow-[0_0_40px_rgba(34,197,94,0.07)] transition-all duration-300 hover:scale-[1.02] cursor-default">
                <div class="w-11 h-11 rounded-xl bg-green-500/10 flex items-center justify-center mb-5 group-hover:bg-green-500/15 transition-colors">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-white mb-2">{{ $card['title'] }}</h3>
                <p class="text-sm text-zinc-500 leading-relaxed">{{ $card['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     HOW IT WORKS SECTION
═══════════════════════════════════════════ --}}
<section id="how-it-works" class="py-24 bg-[#0a0a0a]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-5">
            <p class="text-green-400 text-xs font-bold uppercase tracking-widest mb-3">Process</p>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">Up and Running in Minutes</h2>
        </div>
        <p class="text-center text-zinc-500 text-lg mb-16">No complicated setup. No coding. Just connect and copy.</p>

        {{-- Steps --}}
        <div class="relative">
            {{-- Desktop connector line --}}
            <div class="hidden lg:block absolute top-7 left-[12%] right-[12%] h-px bg-gradient-to-r from-transparent via-zinc-700 to-transparent pointer-events-none"></div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-8 relative">
                @foreach ([
                    ['01', 'Create Your Account',       'Sign up free and verify your email. Takes less than 60 seconds.'],
                    ['02', 'Connect Your Deriv Account', 'Generate a Deriv API token and paste it into your dashboard. We verify it instantly and never store it in plain text.'],
                    ['03', 'Choose a Master Trader',     'Browse our leaderboard and pick a trader whose style matches your goals. Set your stake amount and risk limits.'],
                    ['04', 'Copy Trades Automatically',  'Sit back. Every trade the master places is mirrored to your account in real time. Watch your balance update live.'],
                ] as [$num, $title, $desc])
                <div class="flex flex-col items-center text-center">
                    <div class="w-14 h-14 rounded-full bg-green-500 flex items-center justify-center text-black font-extrabold text-sm shadow-lg shadow-green-500/30 mb-5 relative z-10 flex-shrink-0">
                        {{ $num }}
                    </div>
                    <h3 class="text-sm font-bold text-white mb-2">{{ $title }}</h3>
                    <p class="text-sm text-zinc-500 leading-relaxed">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- CTA card --}}
        <div class="mt-20 relative overflow-hidden rounded-2xl border border-green-500/20 bg-gradient-to-br from-green-900/30 via-green-800/10 to-zinc-900/50 p-8 sm:p-12 text-center">
            <div class="absolute inset-0 bg-[linear-gradient(rgba(34,197,94,.025)_1px,transparent_1px),linear-gradient(90deg,rgba(34,197,94,.025)_1px,transparent_1px)] bg-[size:40px_40px] pointer-events-none"></div>
            <div class="relative">
                <h3 class="text-2xl sm:text-3xl font-extrabold text-white mb-3">Ready to start?</h3>
                <p class="text-zinc-400 text-base max-w-md mx-auto mb-8">
                    Join hundreds of traders copying smarter. Free to get started — no credit card required.
                </p>
                @guest
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-green-500 text-black font-bold text-base hover:bg-green-400 transition-all hover:scale-[1.03] shadow-lg shadow-green-500/30">
                        Create Free Account
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-green-500 text-black font-bold text-base hover:bg-green-400 transition-all hover:scale-[1.03] shadow-lg shadow-green-500/30">
                        Go to Dashboard
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                @endauth
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     FAQ SECTION
═══════════════════════════════════════════ --}}
<section id="faq" class="py-24 bg-[#070707]">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <p class="text-green-400 text-xs font-bold uppercase tracking-widest mb-3">FAQ</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Frequently Asked Questions</h2>
        </div>
        <div class="space-y-2">
            @foreach ([
                [
                    'q' => 'Is my Deriv account safe?',
                    'a' => "Absolutely. Your API token is stored with military-grade encryption and is never shared with anyone. All trades are placed directly in YOUR Deriv account — we never hold or manage your funds. We access your account only to place the mirrored trades you've authorized.",
                ],
                [
                    'q' => 'How much does it cost?',
                    'a' => "Getting started is completely free. We earn a small markup on trades processed through our platform via the official Deriv API markup system. This is built into the Deriv ecosystem and doesn't add any visible cost to you. There are no subscription fees or hidden charges.",
                ],
                [
                    'q' => 'How fast are trades copied?',
                    'a' => "We use a persistent WebSocket connection to Deriv, so trades are detected and copied in seconds — typically under 3 seconds from when the master places a trade. Execution speed depends on network conditions and Deriv's servers.",
                ],
                [
                    'q' => 'Can I set limits to protect myself?',
                    'a' => "Yes. You can set a daily loss limit per master you follow. Once hit, copying automatically pauses for the rest of the day. You can also manually pause or stop copying any time from your dashboard with a single click.",
                ],
                [
                    'q' => 'What stake sizing options are available?',
                    'a' => "Three modes: Proportional (scales your stake relative to your balance vs master balance), Fixed (always use a set dollar amount per trade), or Multiplier (copy at X times the master's stake amount). Each follower-master link can have its own mode.",
                ],
                [
                    'q' => 'Can I follow multiple masters?',
                    'a' => "Yes. You can connect multiple follower accounts and link each to a different master with completely separate risk settings, loss limits, and copy modes per master-follower pair.",
                ],
                [
                    'q' => 'What is Deriv?',
                    'a' => "Deriv (formerly Binary.com) is a regulated online trading platform offering binary options, CFDs, and synthetic indices available 24/7. CopyTrade Pro uses their official WebSocket API — no screen scraping, no unofficial methods.",
                ],
                [
                    'q' => 'Can I become a master trader?',
                    'a' => "Yes! Connect your account as a Master, and other users can follow you. As more people copy your trades, it costs you nothing extra. Your trades are simply mirrored to follower accounts after you place them in your Deriv account.",
                ],
            ] as $faq)
            <div x-data="{ open: false }" class="rounded-xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">
                <button
                    @click="open = !open"
                    class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-zinc-800/30 transition-colors gap-4"
                >
                    <span class="text-sm font-semibold text-white">{{ $faq['q'] }}</span>
                    <svg
                        :class="open ? 'rotate-180' : ''"
                        class="w-4 h-4 text-zinc-500 flex-shrink-0 transition-transform duration-200"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="px-5 pb-5"
                >
                    <p class="text-sm text-zinc-400 leading-relaxed border-t border-zinc-800 pt-4">{{ $faq['a'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════ --}}
<footer class="bg-zinc-950 border-t-2 border-green-500/30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid md:grid-cols-3 gap-10 lg:gap-16">

            {{-- Col 1: Brand --}}
            <div>
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-green-500 flex items-center justify-center">
                        <svg class="w-4 h-4 text-black" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                        </svg>
                    </div>
                    <span class="text-base font-bold">CopyTrade Pro</span>
                </div>
                <p class="text-sm text-zinc-500 leading-relaxed mb-5">
                    The professional copy trading platform for Deriv traders. Automate your strategy, protect your capital.
                </p>
                <span class="inline-flex items-center gap-1.5 text-xs text-zinc-600 bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-1.5">
                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                    Built on Deriv API
                </span>
            </div>

            {{-- Col 2: Quick links --}}
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest text-zinc-400 mb-5">Quick Links</h4>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                    @foreach ([
                        ['Home',         '/'],
                        ['About',        '#about'],
                        ['Services',     '#services'],
                        ['How It Works', '#how-it-works'],
                        ['FAQ',          '#faq'],
                        ['Login',        route('login')],
                        ['Register',     route('register')],
                    ] as [$label, $href])
                    <a href="{{ $href }}" class="text-sm text-zinc-500 hover:text-white transition-colors py-0.5">{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            {{-- Col 3: Risk warning --}}
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest text-zinc-400 mb-5">Risk Warning</h4>
                <p class="text-xs text-zinc-600 leading-relaxed">
                    Trading in financial instruments involves substantial risk of loss and is not suitable for all investors.
                    Past performance of master traders is not indicative of future results.
                </p>
                <p class="text-xs text-zinc-600 leading-relaxed mt-3">
                    CopyTrade Pro is a technology service only and does not provide investment advice.
                    You are solely responsible for all trading decisions made through our platform.
                </p>
                <p class="text-xs text-zinc-700 leading-relaxed mt-3">
                    This service is not affiliated with, endorsed by, or in any way officially connected with Deriv.com or its parent companies.
                </p>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="mt-12 pt-6 border-t border-zinc-800/60 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-zinc-600">© {{ date('Y') }} CopyTrade Pro. All rights reserved.</p>
            <p class="text-xs text-zinc-700">Not affiliated with Deriv.com</p>
        </div>
    </div>
</footer>

</body>
</html>
