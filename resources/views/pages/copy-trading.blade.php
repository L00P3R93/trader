<x-layouts::app :title="__('Copy Trading')">
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8">

        @if(session('success'))
            <flux:callout variant="success" icon="check-circle">
                <flux:callout.heading>{{ session('success') }}</flux:callout.heading>
            </flux:callout>
        @endif

        @if(session('settings_saved'))
            <flux:callout variant="success" icon="check-circle">
                <flux:callout.heading>{{ session('settings_saved') }}</flux:callout.heading>
            </flux:callout>
        @endif

        <div>
            <flux:heading size="xl">Copy Trading</flux:heading>
            <flux:text class="mt-1 text-zinc-500">
                Follow a master trader and automatically copy their winning trades.
            </flux:text>
        </div>

        @if(! auth()->user()->hasDerivConnected())
            <div class="rounded-xl border border-[#1F2937] bg-[#0B1220] px-6 py-10 text-center">
                <div class="mx-auto mb-3 w-fit rounded-full bg-zinc-800 p-4">
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
            <livewire:copy-trading.dashboard />
        @endif

    </div>
</x-layouts::app>
