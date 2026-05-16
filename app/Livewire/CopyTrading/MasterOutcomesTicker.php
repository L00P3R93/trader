<?php

namespace App\Livewire\CopyTrading;

use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MasterOutcomesTicker extends Component
{
    public int $connectionId;

    /** All recorded master trade outcomes in chronological order (1=win, 0=loss). */
    #[Computed]
    public function outcomes(): array
    {
        $raw = Redis::lrange("master_outcomes:{$this->connectionId}", 0, -1);

        return array_map('intval', array_reverse($raw));
    }

    public function render(): View
    {
        return view('livewire.copy-trading.master-outcomes-ticker');
    }
}
