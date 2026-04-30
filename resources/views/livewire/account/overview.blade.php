<div class="space-y-6">
    {{-- Eagerly trigger computed properties so $apiError is set before the alerts block. --}}
    @php $accounts = $this->accounts; $liveBalance = $this->balance; $apiError = $this->apiError; @endphp

    @if(! auth()->user()->hasDerivConnected())
        <div class="rounded-xl border border-zinc-200 bg-white px-6 py-10 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mx-auto mb-3 w-fit rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
                <flux:icon.link class="size-6 text-zinc-400" />
            </div>
            <flux:heading size="sm">No Deriv account connected</flux:heading>
            <flux:text class="mb-4 mt-1 text-zinc-500">Connect your Deriv account to view account details.</flux:text>
            <flux:button href="{{ route('deriv.connect') }}" variant="primary" icon="link">Connect Deriv Account</flux:button>
        </div>
    @else

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Account Overview</flux:heading>
                <flux:text class="mt-1 text-zinc-500">Your Deriv account details and balances.</flux:text>
            </div>
            <flux:button wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh" size="sm" variant="ghost" icon="arrow-path">
                <span wire:loading.remove wire:target="refresh">Refresh</span>
                <span wire:loading wire:target="refresh">Refreshing…</span>
            </flux:button>
        </div>

        {{-- Alerts --}}
        @if($resetSuccess)
            <flux:callout variant="success" icon="check-circle">
                <flux:callout.heading>{{ $resetSuccess }}</flux:callout.heading>
            </flux:callout>
        @endif

        @if($resetError)
            <flux:callout variant="danger" icon="x-circle">
                <flux:callout.heading>{{ $resetError }}</flux:callout.heading>
            </flux:callout>
        @endif

        @if($apiError)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-800/40 dark:bg-amber-900/20">
                <div class="flex gap-3">
                    <flux:icon.exclamation-triangle class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                    <div>
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">{{ $apiError }}</p>
                        <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">
                            Your token may be expired.
                            <a href="{{ route('deriv.connect') }}" class="underline underline-offset-2">Reconnect</a> to restore access.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Profile Summary --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
                <flux:heading size="lg">Profile</flux:heading>
            </div>
            <div class="px-6 py-5">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Name</p>
                        <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ auth()->user()->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Email</p>
                        <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ auth()->user()->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Platform Account</p>
                        <p class="mt-1 font-mono text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ auth()->user()->account_no ?? '—' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Live Balance (from WebSocket) --}}
        @if(! empty($liveBalance))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 dark:border-emerald-800/40 dark:bg-emerald-900/10">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Live Balance</p>
                            <div class="mt-1 flex items-end gap-2">
                                <span class="text-3xl font-bold text-zinc-900 dark:text-white">
                                    {{ number_format((float)($liveBalance['balance'] ?? 0), 2) }}
                                </span>
                                <span class="mb-0.5 text-lg font-medium text-zinc-500">
                                    {{ $liveBalance['currency'] ?? '' }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-zinc-400">
                                Account: {{ $liveBalance['loginid'] ?? '—' }}
                            </p>
                        </div>
                        <div class="rounded-full bg-emerald-500/10 p-3">
                            <flux:icon.banknotes class="size-6 text-emerald-500" />
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Account List from REST API --}}
        @if(! empty($accounts))
            {{-- Real Accounts --}}
            @if(! empty($this->realAccounts))
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
                        <flux:heading size="lg">Real Accounts</flux:heading>
                        <flux:text class="mt-0.5 text-sm text-zinc-500">
                            {{ count($this->realAccounts) }} {{ Str::plural('account', count($this->realAccounts)) }}
                        </flux:text>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($this->realAccounts as $account)
                            @php
                                $accountId   = $account['account_id'] ?? $account['loginid'] ?? '—';
                                $currency    = strtoupper($account['currency'] ?? '');
                                $balance     = isset($account['balance']) ? number_format((float)$account['balance'], 2) : null;
                                $productType = $account['product_type'] ?? 'options';
                                $isCfd       = $productType === 'cfd';
                            @endphp
                            <div class="flex items-center justify-between px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div @class(['flex h-9 w-9 items-center justify-center rounded-full', 'bg-emerald-500/10' => !$isCfd, 'bg-purple-500/10' => $isCfd])>
                                        @if($isCfd)
                                            <flux:icon.chart-bar class="size-4 text-purple-500" />
                                        @else
                                            <flux:icon.building-library class="size-4 text-emerald-500" />
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $accountId }}</p>
                                        <p class="text-xs text-zinc-500">{{ $account['landing_company_name'] ?? $account['broker'] ?? 'Real' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($balance !== null)
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $balance }}</span>
                                    @endif
                                    <span class="text-sm font-medium text-zinc-500">{{ $currency }}</span>
                                    @if($isCfd)
                                        <span class="rounded-full bg-purple-500/10 px-2.5 py-0.5 text-xs font-medium text-purple-600 dark:text-purple-400">CFD</span>
                                    @else
                                        <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">Options</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Demo Accounts --}}
            @if(! empty($this->demoAccounts))
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
                        <flux:heading size="lg">Demo Accounts</flux:heading>
                        <flux:text class="mt-0.5 text-sm text-zinc-500">Practice with virtual money — no risk.</flux:text>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($this->demoAccounts as $account)
                            @php
                                $accountId   = $account['account_id'] ?? $account['loginid'] ?? '—';
                                $currency    = strtoupper($account['currency'] ?? '');
                                $balance     = isset($account['balance']) ? number_format((float)$account['balance'], 2) : null;
                                $productType = $account['product_type'] ?? 'options';
                                $isCfd       = $productType === 'cfd';
                            @endphp
                            <div class="flex items-center justify-between px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-500/10">
                                        @if($isCfd)
                                            <flux:icon.chart-bar class="size-4 text-blue-500" />
                                        @else
                                            <flux:icon.beaker class="size-4 text-blue-500" />
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $accountId }}</p>
                                        <p class="text-xs text-zinc-500">{{ $isCfd ? 'MT5 demo' : 'Demo account' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($balance !== null)
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $balance }}</span>
                                    @endif
                                    <span class="text-sm font-medium text-zinc-500">{{ $currency }}</span>
                                    @if($isCfd)
                                        <span class="rounded-full bg-purple-500/10 px-2.5 py-0.5 text-xs font-medium text-purple-600 dark:text-purple-400">CFD</span>
                                    @else
                                        <span class="rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-medium text-blue-600 dark:text-blue-400">Options</span>
                                        <flux:button
                                            wire:click="resetDemoBalance('{{ $accountId }}')"
                                            wire:confirm="Reset demo balance to $10,000?"
                                            wire:loading.attr="disabled"
                                            size="xs"
                                            variant="ghost"
                                            icon="arrow-path"
                                        >
                                            Reset
                                        </flux:button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        @elseif(! $apiError)
            {{-- Loading / empty state --}}
            <div class="rounded-xl border border-zinc-200 bg-white px-6 py-10 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mx-auto mb-3 w-fit rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
                    <flux:icon.building-library class="size-6 text-zinc-400" />
                </div>
                <flux:heading size="sm">No accounts found</flux:heading>
                <flux:text class="mt-1 text-zinc-500">No Options accounts were returned for your Deriv login.</flux:text>
            </div>
        @endif

        {{-- Connection details --}}
        @php $conn = auth()->user()->derivConnection; @endphp
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
                <flux:heading size="lg">Connection Details</flux:heading>
            </div>
            <div class="grid grid-cols-1 gap-3 px-6 py-5 sm:grid-cols-3">
                <div class="rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Token Type</p>
                    <p class="mt-1 font-semibold capitalize text-zinc-900 dark:text-white">{{ $conn->token_type }}</p>
                </div>
                <div class="rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Scopes</p>
                    <p class="mt-1 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $conn->scope ?? 'trade account_manage' }}</p>
                </div>
                <div class="rounded-lg bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Token Expires</p>
                    <p class="mt-1 text-sm font-medium {{ $conn->isExpired() ? 'text-red-500' : 'text-zinc-900 dark:text-white' }}">
                        @if($conn->expires_at)
                            @if($conn->isExpired()) Expired @else {{ $conn->expires_at->diffForHumans() }} @endif
                        @else
                            No expiry set
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex gap-2 border-t border-zinc-100 px-6 py-4 dark:border-zinc-800">
                <flux:button href="{{ route('deriv.connect') }}" size="sm" variant="ghost" icon="arrow-path">
                    Reconnect
                </flux:button>
                <form method="POST" action="{{ route('deriv.disconnect') }}">
                    @csrf @method('DELETE')
                    <flux:button type="submit" size="sm" variant="danger">Disconnect</flux:button>
                </form>
            </div>
        </div>

    @endif

</div>
