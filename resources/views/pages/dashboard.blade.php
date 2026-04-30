<x-layouts::app :title="__('Dashboard')">
    <div class="max-w-5xl mx-auto space-y-8 py-8 px-4">

            {{-- Alerts --}}
            @if(session('success'))
                <flux:callout variant="success" icon="check-circle" class="animate-in fade-in slide-in-from-top-2">
                    <flux:callout.heading>{{ session('success') }}</flux:callout.heading>
                </flux:callout>
            @endif

            @if(session('error'))
                <flux:callout variant="danger" icon="x-circle" class="animate-in fade-in slide-in-from-top-2">
                    <flux:callout.heading>{{ session('error') }}</flux:callout.heading>
                </flux:callout>
            @endif

            {{-- Header --}}
            <div>
                <flux:heading size="xl" class="font-semibold">
                    Welcome back, {{ auth()->user()->name }} 👋
                </flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                    Here's what's happening with your trading account.
                </flux:text>
            </div>

            {{-- Stats Row --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</flux:text>
                        <div class="rounded-lg bg-zinc-100 p-2 dark:bg-zinc-800">
                            <flux:icon.signal class="size-4 text-zinc-500 dark:text-zinc-400" />
                        </div>
                    </div>
                    <div class="mt-2">
                        @if(auth()->user()->hasDerivConnected())
                            <span class="text-lg font-semibold text-emerald-600 dark:text-emerald-400">Active</span>
                        @else
                            <span class="text-lg font-semibold text-zinc-400">Inactive</span>
                        @endif
                    </div>
                    <flux:text class="mt-1 text-xs text-zinc-400">Deriv connection status</flux:text>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Account</flux:text>
                        <div class="rounded-lg bg-zinc-100 p-2 dark:bg-zinc-800">
                            <flux:icon.identification class="size-4 text-zinc-500 dark:text-zinc-400" />
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ auth()->user()->account_no }}
                        </span>
                    </div>
                    <flux:text class="mt-1 text-xs text-zinc-400">Your platform account</flux:text>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Role</flux:text>
                        <div class="rounded-lg bg-zinc-100 p-2 dark:bg-zinc-800">
                            <flux:icon.shield-check class="size-4 text-zinc-500 dark:text-zinc-400" />
                        </div>
                    </div>
                    <div class="mt-2">
                        @if(auth()->user()->isAdmin())
                            <span class="text-lg font-semibold text-violet-600 dark:text-violet-400">Admin</span>
                        @else
                            <span class="text-lg font-semibold text-zinc-900 dark:text-white">User</span>
                        @endif
                    </div>
                    <flux:text class="mt-1 text-xs text-zinc-400">Platform access level</flux:text>
                </div>
            </div>

            {{-- Deriv Connection Card --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="lg">Deriv Account</flux:heading>
                            <flux:text class="mt-0.5 text-sm text-zinc-500">
                                Connect your Deriv account to enable copy trading.
                            </flux:text>
                        </div>
                        @if(auth()->user()->hasDerivConnected())
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
                                Connected
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                <span class="h-2 w-2 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
                                Not Connected
                            </span>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-5">
                    @if(auth()->user()->hasDerivConnected())
                        @php $conn = auth()->user()->derivConnection; @endphp

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div class="rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50">
                                    <flux:text class="text-xs font-medium text-zinc-400 uppercase tracking-wide">Token Type</flux:text>
                                    <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $conn->token_type }}</p>
                                </div>
                                <div class="rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50">
                                    <flux:text class="text-xs font-medium text-zinc-400 uppercase tracking-wide">Scopes</flux:text>
                                    <p class="mt-1 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $conn->scope ?? 'trade account_manage' }}</p>
                                </div>
                                <div class="rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50">
                                    <flux:text class="text-xs font-medium text-zinc-400 uppercase tracking-wide">Expires</flux:text>
                                    <p class="mt-1 text-sm font-medium {{ $conn->isExpired() ? 'text-red-500' : 'text-zinc-900 dark:text-white' }}">
                                        @if($conn->expires_at)
                                            @if($conn->isExpired())
                                                Expired
                                            @else
                                                {{ $conn->expires_at->diffForHumans() }}
                                            @endif
                                        @else
                                            No expiry
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($conn->isExpired())
                                <flux:callout variant="warning" icon="exclamation-triangle">
                                    <flux:callout.heading>Token expired — reconnect to continue trading.</flux:callout.heading>
                                </flux:callout>
                            @endif

                            <div class="flex gap-2">
                                <flux:button href="{{ route('deriv.connect') }}" size="sm" variant="ghost" icon="arrow-path">
                                    Reconnect
                                </flux:button>
                                <form method="POST" action="{{ route('deriv.disconnect') }}">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button type="submit" size="sm" variant="danger">
                                        Disconnect
                                    </flux:button>
                                </form>
                            </div>
                        </div>

                    @else
                        <div class="flex flex-col items-center py-6 text-center">
                            <div class="mb-4 rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
                                <flux:icon.link class="size-6 text-zinc-400" />
                            </div>
                            <flux:heading size="sm">No Deriv account connected</flux:heading>
                            <flux:text class="mb-4 mt-1 max-w-sm text-zinc-500">
                                Connect your Deriv account to start copy trading and access all platform features.
                            </flux:text>
                            <flux:button href="{{ route('deriv.connect') }}" variant="primary" icon="link">
                                Connect Deriv Account
                            </flux:button>
                        </div>
                    @endif
                </div>
            </div>

    </div>
</x-layouts::app>
