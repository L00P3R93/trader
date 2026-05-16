<?php

namespace App\Livewire\CopyTrading;

use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MasterOutcomesTicker extends Component
{
    public int $connectionId;

    public string $pattern = '';

    public bool $patternEnabled = false;

    /** Outcomes since the last copy trade fired for the current user (chronological order). */
    #[Computed]
    public function outcomes(): array
    {
        $raw = Redis::lrange("master_outcomes:{$this->connectionId}", 0, -1);
        $all = array_map('intval', array_reverse($raw)); // oldest → newest

        $offset = (int) Redis::get("master_outcomes_offset:{$this->connectionId}:".auth()->id());

        return array_values(array_slice($all, $offset));
    }

    /** Whether the current tail of outcomes matches the configured pattern. */
    #[Computed]
    public function patternMatched(): bool
    {
        if (! $this->patternEnabled || strlen($this->pattern) === 0) {
            return false;
        }

        $patLen = strlen($this->pattern);
        $outcomes = $this->outcomes;

        if (count($outcomes) < $patLen) {
            return false;
        }

        $tail = array_slice($outcomes, -$patLen);
        $expected = array_map('intval', str_split($this->pattern));

        return $tail === $expected;
    }

    public function render(): View
    {
        return view('livewire.copy-trading.master-outcomes-ticker');
    }
}
