<x-layouts::app :title="__('Trade History')">
    <div class="max-w-5xl mx-auto space-y-6 py-8 px-4">

        <div class="flex items-start justify-between">
            <div>
                <flux:heading size="xl">Trade History</flux:heading>
                <flux:text class="mt-1 text-zinc-500">Your closed trades and account transactions.</flux:text>
            </div>
        </div>

        <livewire:account.trade-history />

    </div>
</x-layouts::app>
