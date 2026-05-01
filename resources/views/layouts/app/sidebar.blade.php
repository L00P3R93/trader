<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#020617]">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-[#1F2937] bg-[#0B1220]">
            <flux:sidebar.header class="border-b border-[#1F2937]">
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="gap-0.5 p-2">
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item
                        icon="home"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')"
                        wire:navigate
                        class="rounded-lg"
                    >
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="arrows-right-left"
                        :href="route('copy-trading')"
                        :current="request()->routeIs('copy-trading')"
                        wire:navigate
                        class="rounded-lg"
                    >
                        {{ __('Copy Trading') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @if(auth()->user()->hasDerivConnected())
                    <flux:sidebar.group :heading="__('Deriv Account')" class="grid">
                        <flux:sidebar.item
                            icon="user-circle"
                            :href="route('account')"
                            :current="request()->routeIs('account')"
                            wire:navigate
                            class="rounded-lg"
                        >
                            {{ __('Account Overview') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="chart-bar"
                            :href="route('trades')"
                            :current="request()->routeIs('trades')"
                            wire:navigate
                            class="rounded-lg"
                        >
                            {{ __('Trade History') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                @if(auth()->user()->isAdmin())
                    <flux:sidebar.group :heading="__('Administration')" class="grid">
                        <flux:sidebar.item
                            icon="chart-pie"
                            :href="route('admin.dashboard')"
                            :current="request()->routeIs('admin.dashboard')"
                            wire:navigate
                            class="rounded-lg"
                        >
                            {{ __('Analytics') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="users"
                            :href="route('admin.users')"
                            :current="request()->routeIs('admin.users')"
                            wire:navigate
                            class="rounded-lg"
                        >
                            {{ __('Users') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="cog-6-tooth"
                            :href="route('admin.settings')"
                            :current="request()->routeIs('admin.settings')"
                            wire:navigate
                            class="rounded-lg"
                        >
                            {{ __('Settings') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            {{-- Deriv connection status in sidebar footer --}}
            <div class="mx-2 mb-2 rounded-lg border border-[#1F2937] bg-[#111827] px-3 py-2">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 shrink-0 rounded-full {{ auth()->user()->hasDerivConnected() ? 'bg-[#22C55E]' : 'bg-zinc-600' }}"></span>
                    <span class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                        {{ auth()->user()->hasDerivConnected() ? 'Deriv connected' : 'Deriv not connected' }}
                    </span>
                </div>
            </div>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        {{-- Mobile Header --}}
        <flux:header class="border-b border-[#1F2937] bg-[#0B1220] lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="flex items-center gap-2 px-1 py-1.5 text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                            <div class="grid flex-1 leading-tight">
                                <div class="flex items-center gap-1.5">
                                    <flux:heading class="truncate text-sm">{{ auth()->user()->name }}</flux:heading>
                                    @if(auth()->user()->isAdmin())
                                        <span class="rounded-full bg-violet-500/15 px-2 py-0.5 text-xs text-violet-500">Admin</span>
                                    @endif
                                </div>
                                <flux:text class="truncate text-xs">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
