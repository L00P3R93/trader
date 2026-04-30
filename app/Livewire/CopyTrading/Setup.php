<?php

namespace App\Livewire\CopyTrading;

use App\Models\CopySetting;
use App\Models\DerivConnection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Setup extends Component
{
    public ?int $selectedMasterId = null;

    public int $minConsecutiveWins = 1;

    public bool $showForm = false;

    public function mount(): void
    {
        $setting = auth()->user()->copySetting;

        if ($setting) {
            $this->selectedMasterId = $setting->master_connection_id;
            $this->minConsecutiveWins = $setting->min_consecutive_wins;
        }
    }

    public function selectMaster(int $connectionId): void
    {
        $this->selectedMasterId = $connectionId;
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $setting = auth()->user()->copySetting;

        $this->selectedMasterId = $setting?->master_connection_id;
        $this->minConsecutiveWins = $setting?->min_consecutive_wins ?? 1;
        $this->showForm = false;
    }

    public function save(): void
    {
        $this->validate([
            'selectedMasterId' => ['required', 'exists:deriv_connections,id'],
            'minConsecutiveWins' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $master = DerivConnection::where('id', $this->selectedMasterId)
            ->where('type', 'master')
            ->firstOrFail();

        CopySetting::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'master_connection_id' => $master->id,
                'min_consecutive_wins' => $this->minConsecutiveWins,
                'is_active' => true,
            ]
        );

        $this->showForm = false;
        $this->dispatch('copy-setting-saved');
        session()->flash('success', 'Copy trading settings saved.');
    }

    public function toggleActive(): void
    {
        $setting = auth()->user()->copySetting;

        if (! $setting) {
            return;
        }

        $setting->update(['is_active' => ! $setting->is_active]);
    }

    public function disconnect(): void
    {
        auth()->user()->copySetting?->delete();

        $this->selectedMasterId = null;
        $this->minConsecutiveWins = 1;
        $this->showForm = false;

        session()->flash('success', 'Copy trading disconnected.');
    }

    #[Computed]
    public function masters(): \Illuminate\Database\Eloquent\Collection
    {
        return DerivConnection::query()
            ->where('type', 'master')
            ->with('user')
            ->withCount('followers')
            ->get();
    }

    #[Computed]
    public function currentSetting(): ?CopySetting
    {
        return auth()->user()->load('copySetting.masterConnection.user')->copySetting;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.copy-trading.setup');
    }
}
