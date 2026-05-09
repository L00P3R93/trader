<?php

namespace App\Livewire\CopyTrading;

use App\Exceptions\DerivApiException;
use App\Jobs\MasterListenerJob;
use App\Models\CopySetting;
use App\Models\CopyTrade;
use App\Models\DerivConnection;
use App\Services\DerivApiService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Dashboard extends Component
{
    // -- View state --
    public string $activeTab = 'summary';

    public bool $settingsOpen = false;

    public bool $showForm = false;

    public bool $showMasterList = false;

    /** Whether the user is configuring self-copy (their own account as master). */
    public bool $selfCopyMode = false;

    // -- Master selection --
    public ?int $selectedMasterId = null;

    /** The specific Deriv account ID to listen on as master (used in self-copy mode). */
    public ?string $masterAccountId = null;

    // -- Settings form properties --
    public float $stake = 1.00;

    public bool $followMasterStake = false;

    public bool $safeMode = false;

    public float $stakeMultiplier = 1.00;

    public ?float $takeProfit = null;

    public ?float $stopLoss = null;

    public int $maxCompound = 0;

    public int $doMartingaleAt = 1;

    public int $maxMartingale = 0;

    public string $ifHitMaxMartingale = 'stop';

    public int $waitForLoss = 0;

    public bool $onlyUse1xWaitForLoss = false;

    public string $followerPattern = '111';

    public bool $patternEnabled = true;

    /** @var array<string> */
    public array $filterMarkets = [];

    /** @var array<string> */
    public array $syntheticIndices = [];

    /** @var array<string> */
    public array $forexPairs = [];

    public ?string $followerAccountId = null;

    // -- Runtime state --
    public bool $paused = false;

    public const AVAILABLE_MARKETS = ['R_10', 'R_25', 'R_50', 'R_75', 'R_100', '1HZ10V', '1HZ25V', '1HZ50V', '1HZ75V', '1HZ100V'];

    public const AVAILABLE_SYNTHETIC = ['DM', 'DD', 'OE', 'UO', 'TNT', 'OUD', 'RCP', 'RF', 'HILO', 'HLT'];

    public const AVAILABLE_FOREX = ['AUD/CAD', 'AUD/CHF', 'AUD/JPY', 'EUR/USD', 'GBP/USD', 'USD/JPY'];

    public function mount(): void
    {
        $setting = auth()->user()->copySetting;

        if (! $setting) {
            return;
        }

        $this->selectedMasterId = $setting->master_connection_id;
        $this->masterAccountId = $setting->master_account_id;
        $this->stake = (float) ($setting->stake ?? 1.00);
        $this->followMasterStake = $setting->follow_master_stake ?? false;
        $this->safeMode = $setting->safe_mode ?? false;
        $this->stakeMultiplier = (float) ($setting->stake_multiplier ?? 1.00);
        $this->takeProfit = $setting->take_profit ? (float) $setting->take_profit : null;
        $this->stopLoss = $setting->stop_loss ? (float) $setting->stop_loss : null;
        $this->maxCompound = (int) ($setting->max_compound ?? 0);
        $this->doMartingaleAt = (int) ($setting->do_martingale_at ?? 1);
        $this->maxMartingale = (int) ($setting->max_martingale ?? 0);
        $this->ifHitMaxMartingale = $setting->if_hit_max_martingale ?? 'stop';
        $this->waitForLoss = (int) ($setting->wait_for_loss ?? 0);
        $this->onlyUse1xWaitForLoss = $setting->only_use_1x_wait_for_loss ?? false;
        $this->followerPattern = $setting->follower_pattern ?? '111';
        $this->patternEnabled = $setting->pattern_enabled ?? true;
        $this->filterMarkets = $setting->filter_markets ?? [];
        $this->syntheticIndices = $setting->synthetic_indices ?? [];
        $this->forexPairs = $setting->forex_pairs ?? [];
        $this->followerAccountId = $setting->follower_account_id;
        $this->paused = ! ($setting->is_active ?? true);

        // Detect if this is a self-copy setup (master connection belongs to current user)
        $ownConnection = auth()->user()->derivConnection;
        if ($ownConnection && $setting->master_connection_id === $ownConnection->id) {
            $this->selfCopyMode = true;
        }
    }

    // ---- Pre-follow flow ----

    public function enterSelfCopyMode(): void
    {
        $ownConnection = auth()->user()->derivConnection;

        if (! $ownConnection) {
            return;
        }

        $this->selfCopyMode = true;
        $this->selectedMasterId = $ownConnection->id;
        $this->showForm = true;
        $this->showMasterList = false;
    }

    public function selectMaster(int $connectionId): void
    {
        $this->selfCopyMode = false;
        $this->masterAccountId = null;
        $this->selectedMasterId = $connectionId;
        $this->showForm = true;
        $this->showMasterList = false;
    }

    public function switchMaster(int $connectionId): void
    {
        $master = DerivConnection::findOrFail($connectionId);

        auth()->user()->copySetting?->update([
            'master_connection_id' => $master->id,
            'master_account_id' => null,
        ]);

        $this->selectedMasterId = $connectionId;
        $this->masterAccountId = null;
        $this->selfCopyMode = false;
        $this->showMasterList = false;
    }

    public function switchToSelfCopy(): void
    {
        $ownConnection = auth()->user()->derivConnection;

        if (! $ownConnection) {
            return;
        }

        auth()->user()->copySetting?->update([
            'master_connection_id' => $ownConnection->id,
            'master_account_id' => $this->masterAccountId,
        ]);

        $this->selectedMasterId = $ownConnection->id;
        $this->selfCopyMode = true;
        $this->showMasterList = false;
    }

    public function cancelForm(): void
    {
        $setting = auth()->user()->copySetting;

        $this->selectedMasterId = $setting?->master_connection_id;
        $this->masterAccountId = $setting?->master_account_id;
        $this->followerPattern = $setting?->follower_pattern ?? '111';
        $this->patternEnabled = $setting?->pattern_enabled ?? true;
        $this->followerAccountId = $setting?->follower_account_id;
        $this->showForm = false;
        $this->showMasterList = false;
        $this->selfCopyMode = false;
    }

    public function follow(): void
    {
        $rules = [
            'selectedMasterId' => ['required', 'exists:deriv_connections,id'],
            'followerPattern' => ['required', 'regex:/^[01]+$/', 'min_digits:1', 'max:20'],
            'patternEnabled' => ['boolean'],
        ];

        $validAccountIds = array_column($this->myAccounts, 'account_id');

        if (! empty($validAccountIds)) {
            $rules['followerAccountId'] = ['required', 'in:'.implode(',', $validAccountIds)];
        }

        if ($this->selfCopyMode) {
            $rules['masterAccountId'] = ['required', 'in:'.implode(',', $validAccountIds)];
        }

        $this->validate($rules);

        CopySetting::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'master_connection_id' => $this->selectedMasterId,
                'master_account_id' => $this->selfCopyMode ? $this->masterAccountId : null,
                'follower_pattern' => $this->followerPattern,
                'pattern_enabled' => $this->patternEnabled,
                'follower_account_id' => $this->followerAccountId ?: null,
                'is_active' => true,
            ]
        );

        $this->showForm = false;
        $this->dispatch('copy-setting-saved');
        session()->flash('success', 'Copy trading settings saved.');
    }

    public function disconnect(): void
    {
        auth()->user()->copySetting?->delete();

        $this->selectedMasterId = null;
        $this->masterAccountId = null;
        $this->followerPattern = '111';
        $this->patternEnabled = true;
        $this->followerAccountId = null;
        $this->showForm = false;
        $this->showMasterList = false;
        $this->selfCopyMode = false;
        $this->paused = false;

        session()->flash('success', 'Copy trading disconnected.');
    }

    // ---- Settings (post-follow) ----

    public function saveSettings(): void
    {
        $this->validate([
            'stake' => ['required', 'numeric', 'min:0.35', 'max:50000'],
            'stakeMultiplier' => ['required', 'numeric', 'min:1', 'max:100'],
            'takeProfit' => ['nullable', 'numeric', 'min:0'],
            'stopLoss' => ['nullable', 'numeric', 'min:0'],
            'followerPattern' => ['required', 'regex:/^[01]+$/', 'max:20'],
            'maxMartingale' => ['integer', 'min:0', 'max:50'],
            'doMartingaleAt' => ['integer', 'min:1', 'max:50'],
        ]);

        $setting = auth()->user()->copySetting;

        if (! $setting) {
            return;
        }

        $validAccountIds = array_column($this->myAccounts, 'account_id');

        if (! empty($validAccountIds) && $this->followerAccountId) {
            $this->validate(['followerAccountId' => ['nullable', 'in:'.implode(',', $validAccountIds)]]);
        }

        if ($this->selfCopyMode && ! empty($validAccountIds) && $this->masterAccountId) {
            $this->validate(['masterAccountId' => ['nullable', 'in:'.implode(',', $validAccountIds)]]);
        }

        $setting->update([
            'master_account_id' => $this->selfCopyMode ? ($this->masterAccountId ?: null) : null,
            'stake' => $this->stake,
            'follow_master_stake' => $this->followMasterStake,
            'safe_mode' => $this->safeMode,
            'stake_multiplier' => $this->stakeMultiplier,
            'take_profit' => $this->takeProfit,
            'stop_loss' => $this->stopLoss,
            'max_compound' => $this->maxCompound,
            'do_martingale_at' => $this->doMartingaleAt,
            'max_martingale' => $this->maxMartingale,
            'if_hit_max_martingale' => $this->ifHitMaxMartingale,
            'wait_for_loss' => $this->waitForLoss,
            'only_use_1x_wait_for_loss' => $this->onlyUse1xWaitForLoss,
            'follower_pattern' => $this->followerPattern,
            'pattern_enabled' => $this->patternEnabled,
            'filter_markets' => $this->filterMarkets,
            'synthetic_indices' => $this->syntheticIndices,
            'forex_pairs' => $this->forexPairs,
            'follower_account_id' => $this->followerAccountId ?: null,
        ]);

        $this->settingsOpen = false;
        session()->flash('settings_saved', 'Settings saved.');
    }

    // ---- Bot controls ----

    public function startBot(): void
    {
        $setting = auth()->user()->copySetting;

        if (! $setting) {
            return;
        }

        $derivConnection = auth()->user()->derivConnection;

        if ($setting->start_balance === null && $derivConnection) {
            $derivApi = app(DerivApiService::class);
            $balance = $derivApi->getBalance($derivConnection);
            $setting->update(['start_balance' => $balance['balance']['balance'] ?? null]);
        }

        $setting->update([
            'is_running' => true,
            'is_active' => true,
        ]);

        // Dispatch the listener immediately; EnsureListenersRunning will restart
        // it within 1 minute if the queue worker isn't available yet.
        if (! Cache::has(MasterListenerJob::heartbeatKey($setting->master_connection_id))) {
            MasterListenerJob::dispatch($setting->master_connection_id);
        }

        $this->paused = false;
    }

    public function stopBot(): void
    {
        $setting = auth()->user()->copySetting;

        if (! $setting) {
            return;
        }

        $setting->update(['is_running' => false]);

        // Clear heartbeat so EnsureListenersRunning does not restart the listener.
        // The running job will notice is_running=false on its next 30-second ping
        // cycle and exit cleanly on its own.
        Cache::forget(MasterListenerJob::heartbeatKey($setting->master_connection_id));
    }

    public function pauseBot(): void
    {
        $setting = auth()->user()->copySetting;

        if (! $setting) {
            return;
        }

        $this->paused = ! $this->paused;
        $setting->update(['is_active' => ! $this->paused]);
    }

    public function resetStats(): void
    {
        CopyTrade::query()
            ->where('user_id', auth()->id())
            ->delete();

        auth()->user()->copySetting?->update([
            'start_balance' => null,
            'is_running' => false,
        ]);

        $this->paused = false;
    }

    public function exportTransactions(): void
    {
        $this->dispatch('export-transactions');
    }

    // ---- Market toggles ----

    public function toggleMarket(string $market): void
    {
        if (in_array($market, $this->filterMarkets)) {
            $this->filterMarkets = array_values(array_filter($this->filterMarkets, fn ($m) => $m !== $market));
        } else {
            $this->filterMarkets[] = $market;
        }
    }

    public function toggleSynthetic(string $index): void
    {
        if (in_array($index, $this->syntheticIndices)) {
            $this->syntheticIndices = array_values(array_filter($this->syntheticIndices, fn ($i) => $i !== $index));
        } else {
            $this->syntheticIndices[] = $index;
        }
    }

    public function toggleForex(string $pair): void
    {
        if (in_array($pair, $this->forexPairs)) {
            $this->forexPairs = array_values(array_filter($this->forexPairs, fn ($p) => $p !== $pair));
        } else {
            $this->forexPairs[] = $pair;
        }
    }

    #[On('copy-setting-saved')]
    public function refreshSetting(): void
    {
        $this->mount();
    }

    // ---- Computed ----

    #[Computed]
    public function setting(): ?CopySetting
    {
        return auth()->user()->load('copySetting.masterConnection.user')->copySetting;
    }

    #[Computed]
    public function masters(): Collection
    {
        return DerivConnection::query()
            ->where('type', 'master')
            ->with('user')
            ->withCount('followers')
            ->get();
    }

    #[Computed]
    public function trades(): Collection
    {
        return CopyTrade::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('traded_at')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $trades = $this->trades;
        $wins = $trades->where('is_win', true);
        $losses = $trades->where('is_win', false);

        return [
            'trade_count' => $trades->count(),
            'win_count' => $wins->count(),
            'loss_count' => $losses->count(),
            'total_stake' => $trades->sum('stake'),
            'total_payout' => $trades->sum('payout'),
            'total_profit' => $trades->sum('profit'),
            'contracts_won' => $wins->count(),
            'contracts_lost' => $losses->count(),
        ];
    }

    /** All accounts for the current user — used for both master and follower selectors. */
    #[Computed]
    public function myAccounts(): array
    {
        $connection = auth()->user()->derivConnection;

        if (! $connection || $connection->isExpired()) {
            return [];
        }

        try {
            return app(DerivApiService::class)->getAccounts($connection);
        } catch (DerivApiException) {
            return [];
        }
    }

    #[Computed]
    public function currentBalance(): ?float
    {
        $connection = auth()->user()->derivConnection;

        if (! $connection) {
            return null;
        }

        try {
            $result = app(DerivApiService::class)->getBalance($connection);

            return (float) ($result['balance']['balance'] ?? 0);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Whether the background listener is currently alive (has a fresh heartbeat). */
    #[Computed]
    public function listenerAlive(): bool
    {
        $setting = $this->setting;

        if (! $setting?->master_connection_id) {
            return false;
        }

        return Cache::has(MasterListenerJob::heartbeatKey($setting->master_connection_id));
    }

    public function render(): View
    {
        return view('livewire.copy-trading.dashboard');
    }
}
