<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#020617] antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-10 w-10 mb-1 items-center justify-center rounded-xl bg-[#CDF12B] shadow-lg shadow-[#CDF12B]/30">
                        <x-app-logo-icon class="size-5 fill-current text-[#0B1220]" />
                    </span>
                    <span class="text-base font-bold tracking-tight">{{ config('app.name', 'CopyTrade Pro') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border border-[#1F2937] bg-[#0B1220] px-8 py-7 shadow-xl">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
