<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#020617] antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <a href="{{ route('home') }}" class="flex items-center justify-center" wire:navigate>
                    <img src="/logo.svg" alt="{{ config('app.name', 'Fully Automated Bots CT') }}" class="h-10 w-auto">
                </a>

                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border border-[#1F2937] bg-[#0B1220] shadow-xl">
                        <div class="px-10 py-8">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
