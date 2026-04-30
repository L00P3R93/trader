<x-layouts::app :title="__('User Management')">
    <div class="max-w-6xl mx-auto space-y-6 py-8 px-4">

            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">User Management</flux:heading>
                    <flux:text class="mt-1">Manage platform users and their permissions.</flux:text>
                </div>
            </div>

            <livewire:admin.user-management />

    </div>
</x-layouts::app>
