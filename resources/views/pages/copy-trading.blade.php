<x-layouts::app :title="__('Copy Trading')">
    <div class="max-w-5xl mx-auto space-y-6 py-8 px-4">

        @if(session('success'))
            <flux:callout variant="success" icon="check-circle">
                <flux:callout.heading>{{ session('success') }}</flux:callout.heading>
            </flux:callout>
        @endif

        <div class="flex items-start justify-between">
            <div>
                <flux:heading size="xl">Copy Trading</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    Follow a master trader and automatically copy their winning trades.
                </flux:text>
            </div>
        </div>

        @if(! auth()->user()->hasDerivConnected())
            <div class="rounded-xl border border-zinc-200 bg-white px-6 py-10 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mx-auto mb-3 w-fit rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
                    <flux:icon.link class="size-6 text-zinc-400" />
                </div>
                <flux:heading size="sm">Connect your Deriv account first</flux:heading>
                <flux:text class="mb-4 mt-1 text-zinc-500">
                    You need a connected Deriv account to use copy trading.
                </flux:text>
                <flux:button href="{{ route('deriv.connect') }}" variant="primary" icon="link">
                    Connect Deriv Account
                </flux:button>
            </div>
        @else
            <livewire:copy-trading.setup />
        @endif

    </div>
</x-layouts::app>
