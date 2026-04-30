<div>
    @if(! empty($this->balance))
        {{-- Balance + Performance Row --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm font-medium text-zinc-500">Balance</flux:text>
                    <div class="rounded-lg bg-emerald-500/10 p-2">
                        <flux:icon.banknotes class="size-4 text-emerald-500" />
                    </div>
                </div>
                <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ number_format((float)($this->balance['balance'] ?? 0), 2) }}
                </p>
                <p class="mt-0.5 text-xs text-zinc-400">{{ $this->balance['currency'] ?? '' }} · {{ $this->balance['loginid'] ?? '' }}</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm font-medium text-zinc-500">Win Rate</flux:text>
                    <div class="rounded-lg {{ $this->performance['win_rate'] >= 50 ? 'bg-emerald-500/10' : 'bg-red-500/10' }} p-2">
                        <flux:icon.trophy class="size-4 {{ $this->performance['win_rate'] >= 50 ? 'text-emerald-500' : 'text-red-500' }}" />
                    </div>
                </div>
                <p class="mt-2 text-2xl font-bold {{ $this->performance['win_rate'] >= 50 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                    {{ $this->performance['win_rate'] }}%
                </p>
                <p class="mt-0.5 text-xs text-zinc-400">{{ $this->performance['wins'] }}W / {{ $this->performance['losses'] }}L (last 20)</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm font-medium text-zinc-500">P&L</flux:text>
                    <div class="rounded-lg {{ $this->performance['pnl_positive'] ? 'bg-emerald-500/10' : 'bg-red-500/10' }} p-2">
                        <flux:icon.arrow-trending-up class="size-4 {{ $this->performance['pnl_positive'] ? 'text-emerald-500' : 'text-red-500' }}" />
                    </div>
                </div>
                <p class="mt-2 text-2xl font-bold {{ $this->performance['pnl_positive'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                    {{ $this->performance['pnl_positive'] ? '+' : '' }}{{ number_format($this->performance['pnl'], 2) }}
                </p>
                <p class="mt-0.5 text-xs text-zinc-400">Last 20 trades</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm font-medium text-zinc-500">Trades</flux:text>
                    <div class="rounded-lg bg-zinc-100 p-2 dark:bg-zinc-800">
                        <flux:icon.chart-bar class="size-4 text-zinc-500" />
                    </div>
                </div>
                <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->performance['trades'] }}</p>
                <p class="mt-0.5 text-xs text-zinc-400">Last 20 fetched</p>
            </div>

        </div>
    @endif
</div>
