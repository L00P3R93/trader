<div wire:poll.3s
     x-data="{ cleared: false }"
     @pattern-matched="cleared = true"
     @outcomes-reset="cleared = false">
    @php
        $outcomes    = $this->outcomes;
        $patLen      = $this->patternEnabled ? strlen($this->pattern) : 0;
        $total       = count($outcomes);
        $matched     = $this->patternMatched;
        $windowStart = max(0, $total - $patLen);
    @endphp

    <div class="flex flex-wrap items-center gap-1.5">
        <span class="mr-1 shrink-0 text-xs font-medium uppercase tracking-wide text-zinc-500">Master Log:</span>

        {{-- Optimistic empty state: shown immediately when a trade is initiated, before
             the server offset is written and the next poll returns empty results. --}}
        <span x-show="cleared" style="display: none" class="text-xs italic text-zinc-600">
            Waiting for master trades…
        </span>

        {{-- Live server data: hidden the moment a trade fires, until the server resets. --}}
        <div x-show="!cleared" class="flex flex-wrap items-center gap-1.5">
            {{-- Unstick 'cleared' if new outcomes arrive that no longer match the pattern.
                 x-init fires only when this element enters the DOM, which happens exactly
                 when the badge disappears (matched→false) while outcomes are still present. --}}
            @if(! $matched && $total > 0)
                <span x-init="$dispatch('outcomes-reset')" style="display:none"></span>
            @endif

            @forelse($outcomes as $idx => $outcome)
                @php
                    $inWindow = $patLen > 0 && $idx >= $windowStart;
                    $winPos   = $idx - $windowStart;
                    $expected = $inWindow ? (int) ($this->pattern[$winPos] ?? -1) : -1;
                    $matches  = $inWindow && $expected === $outcome;
                @endphp
                <span @class([
                    'inline-flex h-6 w-6 shrink-0 items-center justify-center rounded font-mono text-xs font-bold transition-all',
                    'border border-[#22C55E]/30 bg-[#22C55E]/15 text-[#22C55E]'                                                    => $outcome === 1 && !$inWindow,
                    'border border-red-500/30 bg-red-500/15 text-red-400'                                                          => $outcome === 0 && !$inWindow,
                    'border border-[#22C55E]/60 bg-[#22C55E]/30 text-[#22C55E] ring-1 ring-[#22C55E]/40'                          => $outcome === 1 && $inWindow && $matches,
                    'border border-[#22C55E]/30 bg-[#22C55E]/10 text-[#22C55E]/70'                                                => $outcome === 1 && $inWindow && !$matches,
                    'border border-red-500/60 bg-red-500/25 text-red-400 ring-1 ring-red-500/40'                                  => $outcome === 0 && $inWindow && $matches,
                    'border border-red-500/30 bg-red-500/10 text-red-400/70'                                                      => $outcome === 0 && $inWindow && !$matches,
                ])>{{ $outcome }}</span>
            @empty
                {{-- When the server confirms outcomes are empty, release the cleared state. --}}
                <span x-init="$dispatch('outcomes-reset')" class="text-xs italic text-zinc-600">
                    Waiting for master trades…
                </span>
            @endforelse

            @if($matched)
                {{-- When this badge enters the DOM, immediately clear the log display. --}}
                <span x-init="$dispatch('pattern-matched')"
                      class="ml-1 inline-flex items-center gap-1 rounded-full bg-[#22C55E]/15 px-2 py-0.5 text-xs font-semibold text-[#22C55E] animate-pulse">
                    ✓ Pattern matched — trading
                </span>
            @elseif($patternEnabled && strlen($this->pattern) > 0 && $total > 0)
                <span class="ml-1 text-xs text-zinc-600">
                    {{ $total }}/{{ $patLen }} checking
                </span>
            @endif
        </div>
    </div>
</div>
