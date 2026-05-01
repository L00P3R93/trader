<div class="space-y-8">

    {{-- Current Setup Banner --}}
    @if($this->currentSetting)
        @php $setting = $this->currentSetting; @endphp
        <div class="rounded-xl border border-[#1F2937] bg-[#0B1220]">
            <div class="border-b border-[#1F2937] px-6 py-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Your Copy Setup</flux:heading>
                    @if($setting->is_active)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#22C55E]/10 px-3 py-1 text-sm font-medium text-[#22C55E]">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-[#22C55E]"></span>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                            <span class="h-2 w-2 rounded-full bg-zinc-400"></span>
                            Paused
                        </span>
                    @endif
                </div>
            </div>

            <div class="px-6 py-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-[#111827] px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Following Master</p>
                        <p class="mt-1 font-semibold text-white">
                            {{ $setting->masterConnection->user->name }}
                        </p>
                        <p class="text-xs text-zinc-500">{{ $setting->masterConnection->user->email }}</p>
                    </div>
                    <div class="rounded-lg bg-[#111827] px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Trigger Threshold</p>
                        <p class="mt-1 font-semibold text-zinc-900 dark:text-white">
                            {{ $setting->min_consecutive_wins }}
                            {{ Str::plural('consecutive win', $setting->min_consecutive_wins) }}
                        </p>
                        <p class="text-xs text-zinc-500">before copying starts</p>
                    </div>
                    <div class="rounded-lg bg-[#111827] px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Status</p>
                        <p class="mt-1 font-semibold {{ $setting->is_active ? 'text-[#22C55E]' : 'text-zinc-500' }}">
                            {{ $setting->is_active ? 'Copying trades' : 'Paused' }}
                        </p>
                        <p class="text-xs text-zinc-500">updated {{ $setting->updated_at->diffForHumans() }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <flux:button
                        wire:click="toggleActive"
                        wire:loading.attr="disabled"
                        wire:target="toggleActive"
                        size="sm"
                        variant="{{ $setting->is_active ? 'ghost' : 'primary' }}"
                        icon="{{ $setting->is_active ? 'pause' : 'play' }}"
                    >
                        {{ $setting->is_active ? 'Pause Copying' : 'Resume Copying' }}
                    </flux:button>

                    <flux:button
                        wire:click="selectMaster({{ $setting->master_connection_id }})"
                        size="sm"
                        variant="ghost"
                        icon="pencil"
                    >
                        Edit Settings
                    </flux:button>

                    <flux:button
                        wire:click="disconnect"
                        wire:confirm="Stop copy trading and disconnect from this master?"
                        wire:loading.attr="disabled"
                        wire:target="disconnect"
                        size="sm"
                        variant="danger"
                    >
                        Disconnect
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Configure Form --}}
    @if($showForm && $selectedMasterId)
        @php $master = $this->masters->firstWhere('id', $selectedMasterId); @endphp
        <div class="rounded-xl border border-[#1F2937] bg-[#0B1220]">
            <div class="border-b border-[#1F2937] px-6 py-4">
                <flux:heading size="lg">Configure Copy Settings</flux:heading>
                <flux:text class="mt-0.5 text-sm text-zinc-500">
                    Following: <span class="font-medium text-zinc-900 dark:text-white">{{ $master?->user->name }}</span>
                </flux:text>
            </div>

            <div class="space-y-5 px-6 py-5">
                <div>
                    <flux:label>Consecutive Wins Required</flux:label>
                    <flux:text class="mb-3 text-xs text-zinc-500">
                        How many wins in a row the master must have before your account copies the next trade.
                    </flux:text>

                    <div class="flex items-center gap-3">
                        <flux:input
                            wire:model.live="minConsecutiveWins"
                            type="number"
                            min="1"
                            max="50"
                            class="w-28"
                        />
                        <span class="text-sm text-zinc-500">
                            {{ Str::plural('win', $minConsecutiveWins) }} required before copying
                        </span>
                    </div>

                    @error('minConsecutiveWins')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/40 dark:bg-amber-900/20">
                    <div class="flex gap-3">
                        <flux:icon.information-circle class="size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                        <div>
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-300">How this works</p>
                            <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">
                                When the master wins <strong>{{ $minConsecutiveWins }}</strong>
                                {{ Str::plural('trade', $minConsecutiveWins) }} in a row, your account will
                                automatically copy the next trade. The streak counter resets on any loss.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 pt-1">
                    <flux:button
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        variant="primary"
                    >
                        <span wire:loading.remove wire:target="save">Save Settings</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </flux:button>
                    <flux:button wire:click="cancelForm" variant="ghost">Cancel</flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Master Accounts --}}
    <div>
        <div class="mb-4">
            <flux:heading size="lg">Available Masters</flux:heading>
            <flux:text class="text-sm text-zinc-500">Select a master account to copy their trades.</flux:text>
        </div>

        @if($this->masters->isEmpty())
            <div class="rounded-xl border border-[#1F2937] bg-[#0B1220] px-6 py-12 text-center">
                <div class="mx-auto mb-3 w-fit rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
                    <flux:icon.user-group class="size-6 text-zinc-400" />
                </div>
                <flux:heading size="sm">No master accounts yet</flux:heading>
                <flux:text class="mt-1 text-zinc-500">The admin hasn't designated any master traders yet.</flux:text>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($this->masters as $master)
                    @php $isFollowing = $this->currentSetting?->master_connection_id === $master->id; @endphp
                    <div wire:key="{{ $master->id }}"
                         class="rounded-xl border p-5 transition-colors
                             {{ $isFollowing
                                 ? 'border-[#1E45FC]/40 bg-[#1E45FC]/5'
                                 : 'border-[#1F2937] bg-[#0B1220] hover:border-[#1E45FC]/30' }}">

                        <div class="mb-3 flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$master->user->name" :initials="$master->user->initials()" />
                                <div>
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $master->user->name }}</p>
                                    <p class="text-xs text-zinc-500">{{ $master->user->email }}</p>
                                </div>
                            </div>
                            @if($isFollowing)
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#CDF12B]/10 px-2 py-0.5 text-xs font-medium text-[#CDF12B]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#CDF12B]"></span>
                                    Following
                                </span>
                            @endif
                        </div>

                        <p class="mb-4 text-sm text-zinc-500">
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $master->followers_count }}</span>
                            {{ Str::plural('follower', $master->followers_count) }}
                        </p>

                        <flux:button
                            wire:click="selectMaster({{ $master->id }})"
                            size="sm"
                            variant="{{ $isFollowing ? 'ghost' : 'primary' }}"
                            class="w-full"
                        >
                            {{ $isFollowing ? 'Edit Settings' : 'Follow This Master' }}
                        </flux:button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
