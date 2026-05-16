<div wire:poll.3s>
    @php
        $outcomes   = $this->outcomes;
        $patLen     = $this->patternEnabled ? strlen($this->pattern) : 0;
        $total      = count($outcomes);
        $matched    = $this->patternMatched;
        // Index from which the current "checking window" starts (last $patLen items)
        $windowStart = max(0, $total - $patLen);
    @endphp

    <div class="flex flex-wrap items-center gap-1.5">
        <span class="mr-1 shrink-0 text-xs font-medium uppercase tracking-wide text-zinc-500">Master Log:</span>

        @forelse($outcomes as $idx => $outcome)
            @php
                $inWindow  = $patLen > 0 && $idx >= $windowStart;
                $winPos    = $idx - $windowStart;
                $expected  = $inWindow ? (int) ($this->pattern[$winPos] ?? -1) : -1;
                $matches   = $inWindow && $expected === $outcome;
            @endphp
            <span @class([
                'inline-flex h-6 w-6 shrink-0 items-center justify-center rounded font-mono text-xs font-bold transition-all',
                // Win square — base colours
                'border border-[#22C55E]/30 bg-[#22C55E]/15 text-[#22C55E]' => $outcome === 1 && !$inWindow,
                // Loss square — base colours
                'border border-red-500/30 bg-red-500/15 text-red-400'       => $outcome === 0 && !$inWindow,
                // In-window win — brighter glow when matched
                'border border-[#22C55E]/60 bg-[#22C55E]/30 text-[#22C55E] ring-1 ring-[#22C55E]/40' => $outcome === 1 && $inWindow && $matches,
                // In-window win — mismatch
                'border border-[#22C55E]/30 bg-[#22C55E]/10 text-[#22C55E]/70' => $outcome === 1 && $inWindow && !$matches,
                // In-window loss — matched
                'border border-red-500/60 bg-red-500/25 text-red-400 ring-1 ring-red-500/40' => $outcome === 0 && $inWindow && $matches,
                // In-window loss — mismatch
                'border border-red-500/30 bg-red-500/10 text-red-400/70' => $outcome === 0 && $inWindow && !$matches,
            ])>{{ $outcome }}</span>
        @empty
            <span class="text-xs italic text-zinc-600">Waiting for master trades…</span>
        @endforelse

        @if($matched)
            <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-[#22C55E]/15 px-2 py-0.5 text-xs font-semibold text-[#22C55E] animate-pulse">
                ✓ Pattern matched — trading
            </span>
        @elseif($patternEnabled && strlen($this->pattern) > 0 && $total > 0)
            <span class="ml-1 text-xs text-zinc-600">
                {{ $total }}/{{ $patLen }} checking
            </span>
        @endif
    </div>
</div>
