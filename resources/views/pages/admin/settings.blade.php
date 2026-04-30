<x-layouts::app :title="__('Platform Settings')">
    <div class="max-w-4xl mx-auto space-y-6 py-8 px-4">

        <div>
            <flux:heading size="xl">Platform Settings</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Configure global platform settings and Deriv integration.</flux:text>
        </div>

        {{-- Deriv Integration --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
                <flux:heading size="lg">Deriv Integration</flux:heading>
                <flux:text class="mt-0.5 text-sm text-zinc-500">WebSocket API and OAuth configuration.</flux:text>
            </div>
            <div class="space-y-4 px-6 py-5">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">App ID</p>
                        <p class="mt-1 font-mono text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ config('deriv.app_id') ?? 'Not configured' }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">OAuth Redirect URI</p>
                        <p class="mt-1 font-mono text-xs text-zinc-700 dark:text-zinc-300 break-all">
                            {{ config('deriv.redirect_uri') ?: route('deriv.callback') }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">WebSocket Endpoint</p>
                        <p class="mt-1 font-mono text-xs text-zinc-700 dark:text-zinc-300">
                            wss://ws.binaryws.com/websockets/v3
                        </p>
                    </div>
                    <div class="rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">OAuth Scopes</p>
                        <p class="mt-1 font-mono text-xs text-zinc-700 dark:text-zinc-300">
                            trade account_manage
                        </p>
                    </div>
                </div>
                <flux:callout variant="info" icon="information-circle">
                    <flux:callout.heading>Configure via environment variables</flux:callout.heading>
                    <flux:callout.text>
                        Set <code class="font-mono text-xs">DERIV_APP_ID</code> and <code class="font-mono text-xs">DERIV_REDIRECT_URI</code> in your <code class="font-mono text-xs">.env</code> file.
                    </flux:callout.text>
                </flux:callout>
            </div>
        </div>

        {{-- Platform Stats --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
                <flux:heading size="lg">Platform Statistics</flux:heading>
            </div>
            <div class="grid grid-cols-2 gap-px bg-zinc-100 dark:bg-zinc-800 sm:grid-cols-4">
                @php
                    $stats = [
                        ['label' => 'Total Users', 'value' => \App\Models\User::count()],
                        ['label' => 'Connected Accounts', 'value' => \App\Models\DerivConnection::count()],
                        ['label' => 'Master Traders', 'value' => \App\Models\DerivConnection::where('type','master')->count()],
                        ['label' => 'Copy Settings', 'value' => \App\Models\CopySetting::count()],
                    ];
                @endphp
                @foreach($stats as $stat)
                    <div class="bg-white px-6 py-5 dark:bg-zinc-900">
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stat['value'] }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
                <flux:heading size="lg">Quick Actions</flux:heading>
            </div>
            <div class="flex flex-wrap gap-3 px-6 py-5">
                <flux:button href="{{ route('admin.users') }}" variant="ghost" icon="users" wire:navigate>
                    Manage Users
                </flux:button>
                <flux:button href="{{ route('admin.dashboard') }}" variant="ghost" icon="chart-bar" wire:navigate>
                    View Analytics
                </flux:button>
            </div>
        </div>

    </div>
</x-layouts::app>
