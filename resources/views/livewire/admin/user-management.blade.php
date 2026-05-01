<div class="space-y-4">

    {{-- Search --}}
    <div class="flex-1">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name, email or account number…"
            icon="magnifying-glass"
            clearable
        />
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-[#1F2937]">
        <table class="w-full text-sm">
            <thead class="bg-[#111827]">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">User</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Account</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Deriv</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Role</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Joined</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#1F2937] bg-[#0B1220]">
                @forelse($this->users as $user)
                    <tr wire:key="{{ $user->id }}" class="hover:bg-[#111827]/50 transition-colors">

                        {{-- User --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$user->name" :initials="$user->initials()" size="sm" />
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- Account No --}}
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $user->account_no }}</span>
                        </td>

                        {{-- Deriv connection + type --}}
                        <td class="px-4 py-3">
                            @if($user->derivConnection)
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-[#22C55E]/10 px-2.5 py-1 text-xs font-medium text-[#22C55E] w-fit">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[#22C55E]"></span>
                                        Connected
                                    </span>
                                    @if($user->derivConnection->isMaster())
                                        <span class="inline-flex items-center rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-600 dark:text-amber-400 w-fit">
                                            Master
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400 w-fit">
                                            Follower
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                                    <span class="h-1.5 w-1.5 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
                                    Not connected
                                </span>
                            @endif
                        </td>

                        {{-- Admin Role --}}
                        <td class="px-4 py-3">
                            @if($user->is_admin)
                                <span class="inline-flex items-center rounded-full bg-violet-500/10 px-2.5 py-1 text-xs font-medium text-violet-600 dark:text-violet-400">
                                    Admin
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                    User
                                </span>
                            @endif
                        </td>

                        {{-- Joined --}}
                        <td class="px-4 py-3 text-xs text-zinc-500">
                            {{ $user->created_at->format('d M Y') }}
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">

                                {{-- Master toggle (only when connected) --}}
                                @if($user->derivConnection)
                                    <flux:button
                                        wire:click="toggleMaster({{ $user->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleMaster({{ $user->id }})"
                                        size="xs"
                                        variant="{{ $user->derivConnection->isMaster() ? 'filled' : 'ghost' }}"
                                    >
                                        <span wire:loading.remove wire:target="toggleMaster({{ $user->id }})">
                                            {{ $user->derivConnection->isMaster() ? 'Revoke Master' : 'Set Master' }}
                                        </span>
                                        <span wire:loading wire:target="toggleMaster({{ $user->id }})">…</span>
                                    </flux:button>
                                @endif

                                {{-- Admin toggle --}}
                                @if($user->id !== auth()->id())
                                    <flux:button
                                        wire:click="toggleAdmin({{ $user->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleAdmin({{ $user->id }})"
                                        size="xs"
                                        variant="ghost"
                                    >
                                        <span wire:loading.remove wire:target="toggleAdmin({{ $user->id }})">
                                            {{ $user->is_admin ? 'Revoke Admin' : 'Make Admin' }}
                                        </span>
                                        <span wire:loading wire:target="toggleAdmin({{ $user->id }})">…</span>
                                    </flux:button>
                                @else
                                    <span class="text-xs text-zinc-400">You</span>
                                @endif

                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-zinc-400">
                            @if($search)
                                No users found matching "{{ $search }}"
                            @else
                                No users yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($this->users->hasPages())
        <div class="pt-2">
            {{ $this->users->links() }}
        </div>
    @endif

</div>
