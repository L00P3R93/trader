<x-layouts::app :title="__('Platform Settings')">
    <div class="max-w-4xl mx-auto space-y-6 py-8 px-4">

            <div>
                <flux:heading size="xl">Platform Settings</flux:heading>
                <flux:text class="mt-1">Configure global platform settings.</flux:text>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mx-auto mb-3 rounded-full bg-zinc-100 p-4 w-fit dark:bg-zinc-800">
                    <flux:icon.cog-6-tooth class="size-6 text-zinc-400" />
                </div>
                <flux:heading size="sm">Settings coming soon</flux:heading>
                <flux:text class="mt-1 text-zinc-500">Platform configuration options will appear here.</flux:text>
            </div>

    </div>
</x-layouts::app>
