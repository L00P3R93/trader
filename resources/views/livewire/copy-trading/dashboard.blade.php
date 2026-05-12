<div class="space-y-6" wire:poll.15s x-data="{ runSeconds: 0, timer: null }"
    x-init="
        $watch('$wire.setting?.is_running', val => {
            if (val) { timer = setInterval(() => runSeconds++, 1000); }
            else { clearInterval(timer); runSeconds = 0; }
        });
    ">

    @php $setting = $this->setting; @endphp

    {{-- ================================================================ --}}
    {{-- VIEW A: No master followed → show master list / configure form   --}}
    {{-- ================================================================ --}}
    @if(! $setting)

        @if($showForm && $selectedMasterId)
            {{-- ---- Configure Form (before first follow) ---- --}}
            @php $master = $this->masters->firstWhere('id', $selectedMasterId); @endphp
            <div class="rounded-xl border border-[#1F2937] bg-[#0B1220]">
                <div class="flex items-center gap-3 border-b border-[#1F2937] px-6 py-4">
                    <button wire:click="cancelForm" class="text-zinc-400 hover:text-white transition-colors">
                        <flux:icon.arrow-left class="size-5" />
                    </button>
                    <div>
                        <flux:heading size="lg">Configure Copy Settings</flux:heading>
                        <flux:text class="mt-0.5 text-sm text-zinc-500">
                            @if($selfCopyMode)
                                Self-copy: trading your own accounts
                            @else
                                Following: <span class="font-medium text-white">{{ $master?->user->name }}</span>
                            @endif
                        </flux:text>
                    </div>
                </div>

                <div class="space-y-5 px-6 py-5">

                    {{-- Self-copy: master account selector --}}
                    @if($selfCopyMode && count($this->myAccounts) > 0)
                        <div>
                            <flux:label>Master Account <span class="text-amber-400">(trades to copy FROM)</span></flux:label>
                            <flux:text class="mb-3 text-xs text-zinc-500">Choose which of your accounts to copy trades from.</flux:text>
                            <div class="space-y-2">
                                @foreach($this->myAccounts as $account)
                                    @php
                                        $isDemo = ($account['is_demo'] ?? false) || ($account['account_type'] ?? '') === 'demo';
                                        $accLabel = ($isDemo ? 'Demo' : 'Real') . ' — ' . $account['account_id'];
                                        $accSub = number_format($account['balance'] ?? 0, 2) . ' ' . strtoupper($account['currency'] ?? 'USD');
                                    @endphp
                                    <label wire:key="master-acc-{{ $account['account_id'] }}"
                                        class="flex cursor-pointer items-center gap-4 rounded-lg border p-4 transition-colors
                                            {{ $masterAccountId === $account['account_id']
                                                ? 'border-amber-500/50 bg-amber-500/5'
                                                : 'border-[#1F2937] bg-[#111827] hover:border-[#1F2937]/80' }}"
                                    >
                                        <input type="radio" wire:model.live="masterAccountId" value="{{ $account['account_id'] }}"
                                            class="text-amber-400 focus:ring-amber-400" />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-white">{{ $accLabel }}</span>
                                                <span @class([
                                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                                    'bg-amber-500/10 text-amber-400' => $isDemo,
                                                    'bg-[#22C55E]/10 text-[#22C55E]' => !$isDemo,
                                                ])>{{ $isDemo ? 'Demo' : 'Real' }}</span>
                                                <span class="inline-flex items-center rounded-full bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-400">MASTER</span>
                                            </div>
                                            <p class="mt-0.5 text-xs text-zinc-500">Balance: {{ $accSub }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('masterAccountId')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Follower account selector --}}
                    @if(count($this->myAccounts) > 0)
                        <div>
                            <flux:label>
                                @if($selfCopyMode)
                                    Follower Account <span class="text-[#1E45FC]">(trades to copy TO)</span>
                                @else
                                    Your Trading Account
                                @endif
                            </flux:label>
                            <flux:text class="mb-3 text-xs text-zinc-500">
                                @if($selfCopyMode)
                                    Choose which account will receive copied trades.
                                @else
                                    Choose which account (real or demo) will copy trades.
                                @endif
                            </flux:text>
                            <div class="space-y-2">
                                @foreach($this->myAccounts as $account)
                                    @php
                                        $isDemo = ($account['is_demo'] ?? false) || ($account['account_type'] ?? '') === 'demo';
                                        $accLabel = ($isDemo ? 'Demo' : 'Real') . ' — ' . $account['account_id'];
                                        $accSub = number_format($account['balance'] ?? 0, 2) . ' ' . strtoupper($account['currency'] ?? 'USD');
                                    @endphp
                                    <label wire:key="setup-acc-{{ $account['account_id'] }}"
                                        class="flex cursor-pointer items-center gap-4 rounded-lg border p-4 transition-colors
                                            {{ $followerAccountId === $account['account_id']
                                                ? 'border-[#1E45FC]/50 bg-[#1E45FC]/5'
                                                : 'border-[#1F2937] bg-[#111827] hover:border-[#1F2937]/80' }}"
                                    >
                                        <input type="radio" wire:model.live="followerAccountId" value="{{ $account['account_id'] }}"
                                            class="text-[#1E45FC] focus:ring-[#1E45FC]" />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-white">{{ $accLabel }}</span>
                                                <span @class([
                                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                                    'bg-amber-500/10 text-amber-400' => $isDemo,
                                                    'bg-[#22C55E]/10 text-[#22C55E]' => !$isDemo,
                                                ])>{{ $isDemo ? 'Demo' : 'Real' }}</span>
                                            </div>
                                            <p class="mt-0.5 text-xs text-zinc-500">Balance: {{ $accSub }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('followerAccountId')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Pattern --}}
                    <flux:checkbox wire:model.live="patternEnabled" label="Enable Follower Pattern" />
                    <div>
                        <flux:input
                            wire:model.live="followerPattern"
                            label="Slave / Follower Pattern"
                            placeholder="e.g. 111 or 101"
                            :disabled="!$patternEnabled"
                            description="Use 1 for Win, 0 for Loss. Copying starts when master's last outcomes match this sequence."
                        />
                        @error('followerPattern')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        @if($patternEnabled && strlen($followerPattern) > 0 && preg_match('/^[01]+$/', $followerPattern))
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="text-xs text-zinc-400">Pattern:</span>
                                @foreach(str_split($followerPattern) as $bit)
                                    <span @class([
                                        'inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm font-bold',
                                        'bg-[#22C55E]/20 text-[#22C55E] border border-[#22C55E]/40' => $bit === '1',
                                        'bg-red-500/20 text-red-400 border border-red-500/40' => $bit === '0',
                                    ])>{{ $bit === '1' ? 'W' : 'L' }}</span>
                                @endforeach
                                <span class="ml-1 text-xs text-zinc-500">({{ strlen($followerPattern) }} trade{{ strlen($followerPattern) !== 1 ? 's' : '' }} needed)</span>
                            </div>
                        @endif
                    </div>

                    <div class="rounded-lg border border-amber-800/40 bg-amber-900/20 p-4">
                        <div class="flex gap-3">
                            <flux:icon.information-circle class="size-5 shrink-0 text-amber-400" />
                            <p class="text-xs text-amber-400">
                                The bot reads the master's last <strong>{{ strlen($followerPattern) }}</strong> trade result{{ strlen($followerPattern) !== 1 ? 's' : '' }}.
                                When they match <strong class="font-mono text-[#CDF12B]">{{ $followerPattern }}</strong>, the next trade is copied.
                                <strong>1</strong> = master won, <strong>0</strong> = master lost.
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <flux:button wire:click="follow" wire:loading.attr="disabled" wire:target="follow" variant="primary">
                            <span wire:loading.remove wire:target="follow">Start Following</span>
                            <span wire:loading wire:target="follow">Saving…</span>
                        </flux:button>
                        <flux:button wire:click="cancelForm" variant="ghost">Cancel</flux:button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ---- Available Masters + Self-copy option ---- --}}
        <div>
            @if(! $showForm)
                <div class="mb-4">
                    <flux:heading size="lg">Copy Trading Setup</flux:heading>
                    <flux:text class="text-sm text-zinc-500">Follow a platform master or use your own accounts.</flux:text>
                </div>
            @endif

            {{-- Self-copy card --}}
            @php $ownConnection = auth()->user()->derivConnection; @endphp
            @if($ownConnection && !$showForm)
                <div class="mb-6">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-400">Self-Copy (Your Accounts)</p>
                    <div class="rounded-xl border border-amber-800/40 bg-[#0B1220] p-5 hover:border-amber-500/40 transition-colors">
                        <div class="mb-3 flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-900/40">
                                <flux:icon.arrows-right-left class="size-5 text-amber-400" />
                            </div>
                            <div>
                                <p class="font-semibold text-white">Use My Own Account</p>
                                <p class="text-xs text-zinc-500">Copy trades between your own Deriv accounts — e.g. demo → real, or demo → demo.</p>
                            </div>
                        </div>
                        <div class="mb-4 rounded-lg bg-amber-900/10 px-4 py-3 text-xs text-amber-300/80">
                            You can set any of your accounts (demo or real) as the master, and copy trades into any other account.
                        </div>
                        <flux:button wire:click="enterSelfCopyMode" variant="filled" size="sm" class="w-full bg-amber-600 hover:bg-amber-500 text-white">
                            Configure Self-Copy
                        </flux:button>
                    </div>
                </div>
            @endif

            {{-- Platform masters --}}
            @if(! $showForm)
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-400">Platform Masters</p>
                @if($this->masters->isEmpty())
                    <div class="rounded-xl border border-[#1F2937] bg-[#0B1220] px-6 py-12 text-center">
                        <div class="mx-auto mb-3 w-fit rounded-full bg-zinc-800 p-4">
                            <flux:icon.user-group class="size-6 text-zinc-400" />
                        </div>
                        <flux:heading size="sm">No platform masters yet</flux:heading>
                        <flux:text class="mt-1 text-zinc-500">The admin hasn't designated any master traders yet.</flux:text>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($this->masters as $master)
                            <div wire:key="{{ $master->id }}"
                                 class="rounded-xl border border-[#1F2937] bg-[#0B1220] p-5 hover:border-[#1E45FC]/30 transition-colors">
                                <div class="mb-3 flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <flux:avatar :name="$master->user->name" :initials="$master->user->initials()" />
                                        <div>
                                            <p class="font-semibold text-white">{{ $master->user->name }}</p>
                                            <p class="text-xs text-zinc-500">{{ $master->user->email }}</p>
                                        </div>
                                    </div>
                                </div>
                                <p class="mb-4 text-sm text-zinc-500">
                                    <span class="font-medium text-zinc-300">{{ $master->followers_count }}</span>
                                    {{ Str::plural('follower', $master->followers_count) }}
                                </p>
                                <flux:button wire:click="selectMaster({{ $master->id }})" size="sm" variant="primary" class="w-full">
                                    Follow This Master
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

    @else
    {{-- ================================================================ --}}
    {{-- VIEW B: Following a master → full trading dashboard              --}}
    {{-- ================================================================ --}}

        {{-- ===== TOP CONTROL BAR ===== --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#1F2937] bg-[#0B1220] px-5 py-4">
            <div class="flex flex-wrap items-center gap-3">
                {{-- Run / Stop --}}
                @if($setting->is_running)
                    <flux:button wire:click="stopBot" wire:loading.attr="disabled" wire:target="stopBot"
                        variant="danger" icon="stop-circle" size="sm">
                        Stop Bot
                    </flux:button>
                @else
                    <flux:button wire:click="startBot" wire:loading.attr="disabled" wire:target="startBot"
                        variant="primary" icon="play" size="sm">
                        Run
                    </flux:button>
                @endif

                {{-- Pause / Resume --}}
                @if($setting->is_running)
                    <flux:button wire:click="pauseBot" size="sm"
                        variant="{{ $paused ? 'primary' : 'ghost' }}"
                        icon="{{ $paused ? 'play' : 'pause' }}">
                        {{ $paused ? 'Resume' : 'Pause Trade' }}
                    </flux:button>
                @endif

                {{-- Running time --}}
                @if($setting->is_running)
                    <span class="text-xs text-zinc-400">
                        Running:
                        <span x-text="new Date(runSeconds * 1000).toISOString().substr(11, 8)" class="font-mono text-[#CDF12B]">00:00:00</span>
                    </span>
                @endif

                {{-- Status indicator --}}
                <span class="text-sm {{ $setting->is_running ? 'text-[#22C55E]' : 'text-zinc-400' }}">
                    @if($setting->is_running)
                        <span class="mr-1 inline-block h-2 w-2 animate-pulse rounded-full bg-[#22C55E]"></span>
                        Bot is running{{ $paused ? ' (paused)' : '' }}
                    @else
                        <span class="mr-1 inline-block h-2 w-2 rounded-full bg-zinc-500"></span>
                        Bot is not running
                    @endif
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button wire:click="$toggle('settingsOpen')" size="sm" variant="ghost" icon="cog-6-tooth">
                    Settings
                </flux:button>
                <flux:button wire:click="resetStats" wire:confirm="Reset all trade history and stats?" size="sm" variant="ghost" icon="arrow-path">
                    Reset
                </flux:button>
                <flux:button wire:click="disconnect" wire:confirm="Stop copy trading and disconnect from this master?" size="sm" variant="ghost" icon="x-circle">
                    Disconnect
                </flux:button>
            </div>
        </div>

        {{-- ===== LISTENER STATUS ===== --}}
        @if($setting->is_running)
            <div @class([
                'rounded-xl border px-5 py-3 flex items-center gap-3',
                'border-[#22C55E]/30 bg-[#22C55E]/5' => $this->listenerAlive,
                'border-amber-700/40 bg-amber-900/10' => !$this->listenerAlive,
            ])>
                @if($this->listenerAlive)
                    <span class="inline-block h-2.5 w-2.5 shrink-0 animate-pulse rounded-full bg-[#22C55E]"></span>
                    <span class="text-sm font-medium text-[#22C55E]">Listener active</span>
                @else
                    <span class="inline-block h-2.5 w-2.5 shrink-0 animate-pulse rounded-full bg-amber-400"></span>
                    <span class="text-sm font-medium text-amber-300">Listener starting…</span>
                @endif
            </div>
        @endif

        {{-- ===== EXPANDABLE SETTINGS PANEL ===== --}}
        @if($settingsOpen)
        <div class="rounded-xl border border-[#1F2937] bg-[#0B1220]" x-data="{ moreSettings: false }">
            <div class="flex items-center justify-between border-b border-[#1F2937] px-6 py-4">
                <flux:heading size="lg">Trade Settings</flux:heading>
                <flux:button wire:click="$toggle('settingsOpen')" size="sm" variant="ghost" icon="x-mark" />
            </div>

            <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2 xl:grid-cols-3">

                {{-- Master / Change master --}}
                <div class="col-span-full rounded-lg border border-[#1F2937] bg-[#111827] p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Master</p>
                            @if($setting->master_account_id)
                                <p class="mt-1 font-semibold text-white">
                                    Self-copy — <span class="font-mono text-amber-400">{{ $setting->master_account_id }}</span>
                                </p>
                                <p class="text-xs text-zinc-500">Your own account is the master</p>
                            @else
                                <p class="mt-1 font-semibold text-white">{{ $setting->masterConnection?->user?->name }}</p>
                                <p class="text-xs text-zinc-500">{{ $setting->masterConnection?->user?->email }}</p>
                            @endif
                        </div>
                        <flux:button wire:click="$toggle('showMasterList')" size="sm" variant="ghost" icon="arrows-right-left">
                            {{ $showMasterList ? 'Cancel' : 'Change Master' }}
                        </flux:button>
                    </div>

                    @if($showMasterList)
                        <div class="mt-4 space-y-3">

                            {{-- Self-copy option --}}
                            @php $ownConn = auth()->user()->derivConnection; @endphp
                            @if($ownConn)
                                <div class="rounded-lg border border-amber-800/40 bg-[#0B1220] p-3">
                                    <p class="mb-2 text-xs font-semibold text-amber-400">Use My Own Account</p>

                                    {{-- Master account picker --}}
                                    @if(count($this->myAccounts) > 0)
                                        <p class="mb-1 text-xs text-zinc-500">Master account (copy FROM):</p>
                                        <div class="mb-2 space-y-1">
                                            @foreach($this->myAccounts as $account)
                                                @php
                                                    $isDemoAcc = ($account['is_demo'] ?? false) || ($account['account_type'] ?? '') === 'demo';
                                                @endphp
                                                <label class="flex cursor-pointer items-center gap-2 rounded p-1.5 hover:bg-zinc-800">
                                                    <input type="radio" wire:model.live="masterAccountId" value="{{ $account['account_id'] }}"
                                                        class="text-amber-400 focus:ring-amber-400" />
                                                    <span class="text-xs text-white">{{ $isDemoAcc ? 'Demo' : 'Real' }} — {{ $account['account_id'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(! ($setting->master_account_id && $setting->master_connection_id === $ownConn->id))
                                        <flux:button wire:click="switchToSelfCopy" wire:loading.attr="disabled" wire:target="switchToSelfCopy"
                                            size="xs" class="w-full bg-amber-600 hover:bg-amber-500 text-white">
                                            <span wire:loading.remove wire:target="switchToSelfCopy">Switch to Self-Copy</span>
                                            <span wire:loading wire:target="switchToSelfCopy">…</span>
                                        </flux:button>
                                    @else
                                        <span class="inline-flex w-full items-center justify-center gap-1 rounded-full bg-amber-500/10 py-1 text-xs font-medium text-amber-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                            Current (self-copy)
                                        </span>
                                    @endif
                                </div>
                            @endif

                            {{-- Platform masters --}}
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach($this->masters as $master)
                                    @php $isCurrent = $setting->master_connection_id === $master->id && ! $setting->master_account_id; @endphp
                                    <div wire:key="ml-{{ $master->id }}"
                                        class="rounded-lg border p-3 transition-colors
                                            {{ $isCurrent ? 'border-[#1E45FC]/40 bg-[#1E45FC]/5' : 'border-[#1F2937] bg-[#0B1220]' }}">
                                        <div class="mb-2 flex items-center gap-2">
                                            <flux:avatar :name="$master->user->name" :initials="$master->user->initials()" size="sm" />
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-white">{{ $master->user->name }}</p>
                                                <p class="text-xs text-zinc-500">{{ $master->followers_count }} {{ Str::plural('follower', $master->followers_count) }}</p>
                                            </div>
                                        </div>
                                        @if(! $isCurrent)
                                            <flux:button wire:click="switchMaster({{ $master->id }})" wire:loading.attr="disabled" wire:target="switchMaster({{ $master->id }})" size="xs" variant="primary" class="w-full">
                                                <span wire:loading.remove wire:target="switchMaster({{ $master->id }})">Switch</span>
                                                <span wire:loading wire:target="switchMaster({{ $master->id }})">…</span>
                                            </flux:button>
                                        @else
                                            <span class="inline-flex w-full items-center justify-center gap-1 rounded-full bg-[#CDF12B]/10 py-1 text-xs font-medium text-[#CDF12B]">
                                                <span class="h-1.5 w-1.5 rounded-full bg-[#CDF12B]"></span>
                                                Current
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Self-copy: master account in settings --}}
                @if($selfCopyMode && count($this->myAccounts) > 0)
                <div class="col-span-full">
                    <flux:label class="mb-2 block">Master Account <span class="text-amber-400">(copy FROM)</span></flux:label>
                    <flux:text class="mb-3 text-xs text-zinc-500">Which account's trades are being copied.</flux:text>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($this->myAccounts as $account)
                            @php $isDemo = ($account['is_demo'] ?? false) || ($account['account_type'] ?? '') === 'demo'; @endphp
                            <label wire:key="master-set-{{ $account['account_id'] }}"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-colors
                                    {{ $masterAccountId === $account['account_id']
                                        ? 'border-amber-500/50 bg-amber-500/5'
                                        : 'border-[#1F2937] bg-[#111827] hover:border-[#1F2937]/80' }}"
                            >
                                <input type="radio" wire:model.live="masterAccountId" value="{{ $account['account_id'] }}"
                                    class="text-amber-400 focus:ring-amber-400" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-white">{{ $isDemo ? 'Demo' : 'Real' }} — {{ $account['account_id'] }}</span>
                                        <span @class([
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-amber-500/10 text-amber-400' => $isDemo,
                                            'bg-[#22C55E]/10 text-[#22C55E]' => !$isDemo,
                                        ])>{{ $isDemo ? 'Demo' : 'Real' }}</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-zinc-500">{{ number_format($account['balance'] ?? 0, 2) }} {{ strtoupper($account['currency'] ?? 'USD') }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Follower account --}}
                @if(count($this->myAccounts) > 0)
                <div class="col-span-full">
                    <flux:label class="mb-2 block">
                        @if($selfCopyMode)
                            Follower Account <span class="text-[#1E45FC]">(copy TO)</span>
                        @else
                            Your Trading Account
                        @endif
                    </flux:label>
                    <flux:text class="mb-3 text-xs text-zinc-500">Choose which account (real or demo) will copy trades.</flux:text>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($this->myAccounts as $account)
                            @php $isDemo = ($account['is_demo'] ?? false) || ($account['account_type'] ?? '') === 'demo'; @endphp
                            <label wire:key="acc-{{ $account['account_id'] }}"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-colors
                                    {{ $followerAccountId === $account['account_id']
                                        ? 'border-[#1E45FC]/50 bg-[#1E45FC]/5'
                                        : 'border-[#1F2937] bg-[#111827] hover:border-[#1F2937]/80' }}"
                            >
                                <input type="radio" wire:model.live="followerAccountId" value="{{ $account['account_id'] }}"
                                    class="text-[#1E45FC] focus:ring-[#1E45FC]" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-white">{{ $isDemo ? 'Demo' : 'Real' }} — {{ $account['account_id'] }}</span>
                                        <span @class([
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-amber-500/10 text-amber-400' => $isDemo,
                                            'bg-[#22C55E]/10 text-[#22C55E]' => !$isDemo,
                                        ])>{{ $isDemo ? 'Demo' : 'Real' }}</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-zinc-500">{{ number_format($account['balance'] ?? 0, 2) }} {{ strtoupper($account['currency'] ?? 'USD') }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Slave/Follower Pattern --}}
                <div class="col-span-full rounded-lg border border-[#1E45FC]/30 bg-[#1E45FC]/5 p-4">
                    <flux:checkbox wire:model.live="patternEnabled" label="Enable Slave Pattern" class="mb-3" />
                    <flux:input
                        wire:model.live="followerPattern"
                        label="Follower Pattern (1=Win, 0=Loss)"
                        placeholder="e.g. 111 or 101"
                        description="Copy will start when master's last N outcomes match this pattern."
                        :disabled="!$patternEnabled"
                    />
                    @error('followerPattern')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    @if($patternEnabled && strlen($followerPattern) > 0)
                        <div class="mt-2 flex gap-1">
                            @foreach(str_split($followerPattern) as $bit)
                                <span @class([
                                    'inline-flex h-7 w-7 items-center justify-center rounded text-xs font-bold',
                                    'bg-[#22C55E]/20 text-[#22C55E] border border-[#22C55E]/40' => $bit === '1',
                                    'bg-red-500/20 text-red-400 border border-red-500/40' => $bit === '0',
                                ])>{{ $bit === '1' ? 'W' : 'L' }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- More Settings toggle --}}
                <div class="col-span-full border-t border-[#1F2937] pt-2">
                    <button
                        @click="moreSettings = !moreSettings"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-zinc-400 transition-colors hover:bg-[#111827] hover:text-white"
                    >
                        <span x-text="moreSettings ? 'Hide Advanced Settings' : 'More Settings'"></span>
                        <svg x-bind:class="moreSettings ? 'rotate-180' : ''" class="size-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                {{-- Collapsible advanced settings --}}
                <div class="col-span-full" x-show="moreSettings" x-collapse>
                    <div class="grid grid-cols-1 gap-6 pt-2 md:grid-cols-2 xl:grid-cols-3">

                        {{-- Stake --}}
                        <div>
                            <flux:input wire:model="stake" label="Default Stake (USD)" type="number" step="0.01" min="0.35" max="50000" />
                            <flux:checkbox wire:model="followMasterStake" label="Follow Master Stake" class="mt-2" />
                            @error('stake') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        {{-- Martingale & TP/SL --}}
                        <div class="space-y-2">
                            <flux:input wire:model="stakeMultiplier" label="Stake Multiplier ×" type="number" step="0.1" min="1" max="100" />
                            <div class="grid grid-cols-2 gap-2">
                                <flux:input wire:model="takeProfit" label="Take Profit" type="number" min="0" placeholder="—" />
                                <flux:input wire:model="stopLoss" label="Stop Loss" type="number" min="0" placeholder="—" />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <flux:input wire:model="doMartingaleAt" label="Do Martingale at" type="number" min="1" />
                                <flux:input wire:model="maxMartingale" label="Max Martingale" type="number" min="0" />
                            </div>
                            <flux:input wire:model="maxCompound" label="Max Compound" type="number" min="0" />
                            <div>
                                <flux:label>If Hit Max Martingale</flux:label>
                                <flux:select wire:model="ifHitMaxMartingale" size="sm">
                                    <flux:select.option value="stop">Stop</flux:select.option>
                                    <flux:select.option value="continue">Continue</flux:select.option>
                                </flux:select>
                            </div>
                        </div>

                        {{-- Options --}}
                        <div class="space-y-3">
                            <flux:checkbox wire:model="safeMode" label="Safe Mode" />
                            <div>
                                <flux:label>Wait for Loss</flux:label>
                                <flux:input wire:model="waitForLoss" type="number" min="0" class="mt-1 w-24" />
                            </div>
                            <flux:checkbox wire:model="onlyUse1xWaitForLoss" label="Only Use 1× Wait for Loss" />
                        </div>

                        {{-- Filter Indices Market --}}
                        <div>
                            <flux:label class="mb-2 block">Filter Indices Market</flux:label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(self::AVAILABLE_MARKETS as $market)
                                    <button wire:click="toggleMarket('{{ $market }}')"
                                        @class([
                                            'rounded px-2 py-1 text-xs font-medium transition-colors',
                                            'bg-[#1E45FC] text-white' => in_array($market, $filterMarkets),
                                            'bg-[#111827] text-zinc-400 hover:bg-[#1F2937]' => !in_array($market, $filterMarkets),
                                        ])>
                                        {{ $market }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Synthetic Indices --}}
                        <div>
                            <flux:label class="mb-2 block">Synthetic Indices</flux:label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(self::AVAILABLE_SYNTHETIC as $index)
                                    <button wire:click="toggleSynthetic('{{ $index }}')"
                                        @class([
                                            'rounded px-2 py-1 text-xs font-medium transition-colors',
                                            'bg-[#1E45FC] text-white' => in_array($index, $syntheticIndices),
                                            'bg-[#111827] text-zinc-400 hover:bg-[#1F2937]' => !in_array($index, $syntheticIndices),
                                        ])>
                                        {{ $index }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Forex Pairs --}}
                        <div>
                            <flux:label class="mb-2 block">Forex Pairs</flux:label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(self::AVAILABLE_FOREX as $pair)
                                    <button wire:click="toggleForex('{{ $pair }}')"
                                        @class([
                                            'rounded px-2 py-1 text-xs font-medium transition-colors',
                                            'bg-[#1E45FC] text-white' => in_array($pair, $forexPairs),
                                            'bg-[#111827] text-zinc-400 hover:bg-[#1F2937]' => !in_array($pair, $forexPairs),
                                        ])>
                                        {{ $pair }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <div class="flex gap-3 border-t border-[#1F2937] px-6 py-4">
                <flux:button wire:click="saveSettings" wire:loading.attr="disabled" wire:target="saveSettings" variant="primary">
                    <span wire:loading.remove wire:target="saveSettings">Save Settings</span>
                    <span wire:loading wire:target="saveSettings">Saving…</span>
                </flux:button>
                <flux:button wire:click="$toggle('settingsOpen')" variant="ghost">Cancel</flux:button>
            </div>
        </div>
        @endif

        {{-- ===== STATUS CARDS ===== --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Master account --}}
            <div class="rounded-xl border border-[#1F2937] bg-[#0B1220] px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Master Account</p>
                @if($setting->master_account_id)
                    <p class="mt-1 font-mono text-sm font-semibold text-amber-400">{{ $setting->master_account_id }}</p>
                    <p class="text-xs text-zinc-500">Self-copy</p>
                @else
                    <p class="mt-1 text-sm font-semibold text-white">{{ $setting->masterConnection?->user?->name }}</p>
                @endif
                @php $masterConn = $setting->masterConnection; @endphp
                <span @class([
                    'mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                    'bg-[#22C55E]/10 text-[#22C55E]' => $masterConn && !$masterConn->isExpired(),
                    'bg-red-500/10 text-red-400' => !$masterConn || $masterConn->isExpired(),
                ])>
                    <span class="h-1.5 w-1.5 rounded-full {{ $masterConn && !$masterConn->isExpired() ? 'bg-[#22C55E]' : 'bg-red-400' }}"></span>
                    {{ $masterConn && !$masterConn->isExpired() ? 'Connected' : 'Disconnected' }}
                </span>
            </div>

            {{-- Follower account --}}
            @php $myConnection = auth()->user()->derivConnection; @endphp
            <div class="rounded-xl border border-[#1F2937] bg-[#0B1220] px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Follower Account</p>
                @if($setting->follower_account_id)
                    <p class="mt-1 font-mono text-sm font-semibold text-white">{{ $setting->follower_account_id }}</p>
                @else
                    <p class="mt-1 text-sm text-amber-400">No account selected</p>
                @endif
                <span @class([
                    'mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                    'bg-[#22C55E]/10 text-[#22C55E]' => $myConnection && !$myConnection->isExpired(),
                    'bg-red-500/10 text-red-400' => !$myConnection || $myConnection->isExpired(),
                ])>
                    <span class="h-1.5 w-1.5 rounded-full {{ $myConnection && !$myConnection->isExpired() ? 'bg-[#22C55E]' : 'bg-red-400' }}"></span>
                    {{ $myConnection && !$myConnection->isExpired() ? 'Connected' : 'Disconnected' }}
                </span>
            </div>

            {{-- Start balance --}}
            <div class="rounded-xl border border-[#1F2937] bg-[#0B1220] px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Start Balance</p>
                <p class="mt-1 text-lg font-semibold text-white">
                    {{ $setting->start_balance !== null ? number_format($setting->start_balance, 2) . ' USD' : '—' }}
                </p>
            </div>

            {{-- Current balance / P&L --}}
            <div class="rounded-xl border border-[#1F2937] bg-[#0B1220] px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Current Balance</p>
                @if($this->currentBalance !== null)
                    @php
                        $pl = $setting->start_balance !== null ? $this->currentBalance - $setting->start_balance : null;
                        $plPositive = $pl === null || $pl >= 0;
                    @endphp
                    <p class="mt-1 text-lg font-semibold text-white">{{ number_format($this->currentBalance, 2) }} USD</p>
                    @if($pl !== null)
                        <p class="text-xs {{ $plPositive ? 'text-[#22C55E]' : 'text-red-400' }}">
                            P/L: {{ $plPositive ? '+' : '' }}{{ number_format($pl, 2) }} USD
                        </p>
                    @endif
                @else
                    <p class="mt-1 text-lg font-semibold text-zinc-500">—</p>
                @endif
            </div>
        </div>

        {{-- ===== TABS: Summary / Transactions ===== --}}
        <div class="rounded-xl border border-[#1F2937] bg-[#0B1220]">
            <div class="flex border-b border-[#1F2937]">
                <button wire:click="$set('activeTab', 'summary')"
                    @class(['px-5 py-3 text-sm font-medium border-b-2 transition-colors',
                        'border-[#1E45FC] text-[#1E45FC]' => $activeTab === 'summary',
                        'border-transparent text-zinc-400 hover:text-zinc-200' => $activeTab !== 'summary'])>
                    Summary
                </button>
                <button wire:click="$set('activeTab', 'transactions')"
                    @class(['px-5 py-3 text-sm font-medium border-b-2 transition-colors',
                        'border-[#1E45FC] text-[#1E45FC]' => $activeTab === 'transactions',
                        'border-transparent text-zinc-400 hover:text-zinc-200' => $activeTab !== 'transactions'])>
                    Transactions
                    @if($this->stats['trade_count'] > 0)
                        <span class="ml-1.5 rounded-full bg-[#1E45FC]/20 px-2 py-0.5 text-xs text-[#1E45FC]">{{ $this->stats['trade_count'] }}</span>
                    @endif
                </button>
            </div>

            {{-- Summary tab --}}
            @if($activeTab === 'summary')
            <div class="p-6">
                @if($this->stats['trade_count'] === 0)
                    <div class="py-10 text-center text-zinc-500">
                        <flux:icon.chart-bar class="mx-auto mb-3 size-10 opacity-30" />
                        <p class="text-sm">When you're ready to trade, hit <strong class="text-white">Run</strong>.</p>
                        <p class="mt-1 text-xs">You'll be able to track performance here.</p>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div class="rounded-lg bg-[#111827] px-4 py-3">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Trades</p>
                            <p class="mt-1 text-xl font-bold text-white">{{ $this->stats['trade_count'] }}</p>
                            <p class="text-xs text-zinc-500">{{ $this->stats['win_count'] }}W / {{ $this->stats['loss_count'] }}L</p>
                        </div>
                        <div class="rounded-lg bg-[#111827] px-4 py-3">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Total Stake</p>
                            <p class="mt-1 text-xl font-bold text-white">{{ number_format($this->stats['total_stake'], 2) }}</p>
                            <p class="text-xs text-zinc-500">USD</p>
                        </div>
                        <div class="rounded-lg bg-[#111827] px-4 py-3">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Total Payout</p>
                            <p class="mt-1 text-xl font-bold text-white">{{ number_format($this->stats['total_payout'], 2) }}</p>
                            <p class="text-xs text-zinc-500">USD</p>
                        </div>
                        <div class="rounded-lg bg-[#111827] px-4 py-3">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Total Profit</p>
                            @php $profit = $this->stats['total_profit']; @endphp
                            <p class="mt-1 text-xl font-bold {{ $profit >= 0 ? 'text-[#22C55E]' : 'text-red-400' }}">
                                {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit, 2) }}
                            </p>
                            <p class="text-xs text-zinc-500">USD</p>
                        </div>
                    </div>
                @endif
            </div>
            @endif

            {{-- Transactions tab --}}
            @if($activeTab === 'transactions')
            <div class="overflow-x-auto">
                @if($this->trades->isEmpty())
                    <div class="py-10 text-center text-zinc-500">
                        <flux:icon.table-cells class="mx-auto mb-3 size-10 opacity-30" />
                        <p class="text-sm">No transactions yet.</p>
                    </div>
                @else
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#1F2937] text-zinc-400">
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">DateTime</th>
                                <th class="px-4 py-3">FollowerTrxID</th>
                                <th class="px-4 py-3">Dur</th>
                                <th class="px-4 py-3">Barr</th>
                                <th class="px-4 py-3">Stake</th>
                                <th class="px-4 py-3">Payout</th>
                                <th class="px-4 py-3">Profit</th>
                                <th class="px-4 py-3">SellAtMarket</th>
                                <th class="px-4 py-3">MasterTrxID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->trades as $i => $trade)
                                <tr wire:key="{{ $trade->id }}"
                                    @class([
                                        'border-b border-[#1F2937]/50 transition-colors',
                                        'bg-[#0d2318]' => $trade->is_win === true,
                                        'bg-[#1a0d0d]' => $trade->is_win === false,
                                        'hover:bg-[#111827]' => $trade->is_win === null,
                                    ])>
                                    <td class="px-4 py-2 text-zinc-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-2 font-mono text-zinc-300">{{ $trade->traded_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                                    <td class="px-4 py-2 font-mono text-zinc-400">{{ $trade->follower_trx_id ?? '—' }}</td>
                                    <td class="px-4 py-2 text-zinc-300">{{ $trade->duration ?? '—' }}</td>
                                    <td class="px-4 py-2 text-zinc-300">{{ $trade->barrier ?? '—' }}</td>
                                    <td class="px-4 py-2 font-medium text-white">{{ number_format($trade->stake, 2) }}</td>
                                    <td class="px-4 py-2 text-zinc-300">{{ $trade->payout !== null ? number_format($trade->payout, 2) : '—' }}</td>
                                    <td class="px-4 py-2 font-medium {{ $trade->profit !== null && $trade->profit >= 0 ? 'text-[#22C55E]' : 'text-red-400' }}">
                                        {{ $trade->profit !== null ? number_format($trade->profit, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-zinc-400">{{ $trade->sell_at_market ? 'Yes' : 'No' }}</td>
                                    <td class="px-4 py-2 font-mono text-zinc-400">{{ $trade->master_trx_id ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            @endif
        </div>

        {{-- ===== SUMMARY STATS FOOTER ===== --}}
        <div class="rounded-xl border border-[#1F2937] bg-[#0B1220] px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-4 text-sm">
                <div class="flex flex-wrap gap-6 text-zinc-300">
                    <span>Trade: <strong class="text-white">{{ $this->stats['trade_count'] }}</strong></span>
                    <span>Win: <strong class="text-[#22C55E]">{{ $this->stats['win_count'] }}</strong></span>
                    <span>Loss: <strong class="text-red-400">{{ $this->stats['loss_count'] }}</strong></span>
                    <span>Profit:
                        <strong @class(['font-mono', 'text-[#22C55E]' => $this->stats['total_profit'] >= 0, 'text-red-400' => $this->stats['total_profit'] < 0])>
                            {{ $this->stats['total_profit'] >= 0 ? '+' : '' }}{{ number_format($this->stats['total_profit'], 2) }}
                        </strong>
                    </span>
                </div>
                <div class="flex flex-wrap gap-6 text-zinc-300">
                    <span>Total Stake: <strong class="text-white">{{ number_format($this->stats['total_stake'], 2) }} USD</strong></span>
                    <span>Total Payout: <strong class="text-white">{{ number_format($this->stats['total_payout'], 2) }} USD</strong></span>
                    <span>Contracts Won: <strong class="text-[#22C55E]">{{ $this->stats['contracts_won'] }}</strong></span>
                    <span>Contracts Lost: <strong class="text-red-400">{{ $this->stats['contracts_lost'] }}</strong></span>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between">
                <div class="flex items-center gap-3 text-xs text-zinc-400">
                    @if($setting->pattern_enabled && $setting->follower_pattern)
                        <span>Slave Pattern: <strong class="font-mono text-[#CDF12B]">{{ $setting->follower_pattern }}</strong></span>
                    @endif
                </div>
                <flux:button wire:click="exportTransactions" size="sm" variant="ghost" icon="arrow-down-tray">
                    Export Transaction Data
                </flux:button>
            </div>
        </div>

    @endif {{-- end VIEW B --}}

</div>
