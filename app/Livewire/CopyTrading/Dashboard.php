<?php

namespace App\Livewire\CopyTrading;

use App\Exceptions\DerivApiException;
use App\Models\CopySetting;
use App\Models\CopyTrade;
use App\Models\DerivConnection;
use App\Services\DerivApiService;
use Illuminate\Database\Eloquent\Collection;
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

    // -- Master selection --
    public ?int $selectedMasterId = null;

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
    }

    // ---- Pre-follow flow ----

    public function selectMaster(int $connectionId): void
    {
        $this->selectedMasterId = $connectionId;
        $this->showForm = true;
        $this->showMasterList = false;
    }

    public function switchMaster(int $connectionId): void
    {
        $master = DerivConnection::where('id', $connectionId)
            ->where('type', 'master')
            ->firstOrFail();

        auth()->user()->copySetting?->update(['master_connection_id' => $master->id]);

        $this->selectedMasterId = $connectionId;
        $this->showMasterList = false;
    }

    public function cancelForm(): void
    {
        $setting = auth()->user()->copySetting;

        $this->selectedMasterId = $setting?->master_connection_id;
        $this->followerPattern = $setting?->follower_pattern ?? '111';
        $this->patternEnabled = $setting?->pattern_enabled ?? true;
        $this->followerAccountId = $setting?->follower_account_id;
        $this->showForm = false;
        $this->showMasterList = false;
    }

    public function follow(): void
    {
        $rules = [
            'selectedMasterId' => ['required', 'exists:deriv_connections,id'],
            'followerPattern' => ['required', 'regex:/^[01]+$/', 'min_digits:1', 'max:20'],
            'patternEnabled' => ['boolean'],
        ];

        $validAccountIds = array_column($this->followerAccounts, 'account_id');

        if (! empty($validAccountIds)) {
            $rules['followerAccountId'] = ['required', 'in:'.implode(',', $validAccountIds)];
        }

        $this->validate($rules);

        $master = DerivConnection::where('id', $this->selectedMasterId)
            ->where('type', 'master')
            ->firstOrFail();

        CopySetting::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'master_connection_id' => $master->id,
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
        $this->followerPattern = '111';
        $this->patternEnabled = true;
        $this->followerAccountId = null;
        $this->showForm = false;
        $this->showMasterList = false;
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

        $validAccountIds = array_column($this->followerAccounts, 'account_id');

        if (! empty($validAccountIds) && $this->followerAccountId) {
            $this->validate(['followerAccountId' => ['nullable', 'in:'.implode(',', $validAccountIds)]]);
        }

        $setting->update([
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

        $this->paused = false;
    }

    public function stopBot(): void
    {
        auth()->user()->copySetting?->update(['is_running' => false]);
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

    #[Computed]
    public function followerAccounts(): array
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

    public function render(): View
    {
        return view('livewire.copy-trading.dashboard');
    }
}
