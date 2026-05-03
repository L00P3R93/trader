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

    {{-- Master account selector modal --}}
    <flux:modal name="master-account-selector" class="w-[480px]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Select Master Account</flux:heading>
                <flux:text class="mt-0.5 text-sm text-zinc-500">
                    Choose which Deriv account (real or demo) this master trades from.
                </flux:text>
            </div>

            @if($masterAccountError)
                <div class="rounded-lg border border-red-800/40 bg-red-900/20 px-4 py-3 text-sm text-red-400">
                    {{ $masterAccountError }}
                </div>
            @endif

            @if(count($this->masterAccountOptions) === 0)
                <div class="rounded-lg border border-[#1F2937] bg-[#111827] px-4 py-6 text-center text-sm text-zinc-400">
                    @if($masterAccountUserId)
                        No accounts found — token may be expired or the Deriv API is unreachable.
                    @else
                        No master selected.
                    @endif
                </div>
            @else
                <div class="space-y-2">
                    @foreach($this->masterAccountOptions as $account)
                        @php
                            $isDemo = ($account['account_type'] ?? '') === 'demo';
                            $label = ($isDemo ? 'Demo' : 'Real') . ' — ' . $account['account_id'];
                            $sub = number_format($account['balance'] ?? 0, 2) . ' ' . strtoupper($account['currency'] ?? 'USD');
                        @endphp
                        <label wire:key="{{ $account['account_id'] }}"
                            class="flex cursor-pointer items-center gap-4 rounded-lg border p-4 transition-colors
                                {{ $selectedMasterAccountId === $account['account_id']
                                    ? 'border-[#1E45FC]/50 bg-[#1E45FC]/5'
                                    : 'border-[#1F2937] bg-[#111827] hover:border-[#1F2937]/80' }}"
                        >
                            <input
                                type="radio"
                                wire:model.live="selectedMasterAccountId"
                                value="{{ $account['account_id'] }}"
                                class="text-[#1E45FC] focus:ring-[#1E45FC]"
                            />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-white text-sm">{{ $label }}</span>
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-amber-500/10 text-amber-400' => $isDemo,
                                        'bg-[#22C55E]/10 text-[#22C55E]' => !$isDemo,
                                    ])>{{ $isDemo ? 'Demo' : 'Real' }}</span>
                                </div>
                                <p class="text-xs text-zinc-500 mt-0.5">Balance: {{ $sub }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center gap-2 pt-1">
                <flux:button
                    wire:click="saveMasterAccount"
                    wire:loading.attr="disabled"
                    wire:target="saveMasterAccount"
                    variant="primary"
                    :disabled="!$selectedMasterAccountId"
                >
                    <span wire:loading.remove wire:target="saveMasterAccount">Save</span>
                    <span wire:loading wire:target="saveMasterAccount">Saving…</span>
                </flux:button>
                <flux:button wire:click="closeMasterAccountModal" variant="ghost">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>

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

                        {{-- Deriv connection + master account --}}
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
                                        @if($user->derivConnection->master_account_id)
                                            <span class="font-mono text-xs text-zinc-500">
                                                {{ $user->derivConnection->master_account_id }}
                                            </span>
                                        @else
                                            <span class="text-xs text-amber-500/70">No account set</span>
                                        @endif
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

                                {{-- Master account selector (only for masters) --}}
                                @if($user->derivConnection?->isMaster())
                                    <flux:button
                                        wire:click="openMasterAccountSelector({{ $user->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="openMasterAccountSelector({{ $user->id }})"
                                        size="xs"
                                        variant="ghost"
                                        icon="building-office"
                                    >
                                        <span wire:loading.remove wire:target="openMasterAccountSelector({{ $user->id }})">
                                            {{ $user->derivConnection->master_account_id ? 'Change Account' : 'Set Account' }}
                                        </span>
                                        <span wire:loading wire:target="openMasterAccountSelector({{ $user->id }})">…</span>
                                    </flux:button>
                                @endif

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
