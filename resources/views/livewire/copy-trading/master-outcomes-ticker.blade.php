<div wire:poll.3s>
    <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5">
        <span class="mr-1 shrink-0 text-xs font-medium uppercase tracking-wide text-zinc-500">Master Log:</span>
        @forelse($this->outcomes as $outcome)
            <span @class([
                'inline-flex h-6 w-6 shrink-0 items-center justify-center rounded font-mono text-xs font-bold',
                'border border-[#22C55E]/30 bg-[#22C55E]/15 text-[#22C55E]' => $outcome === 1,
                'border border-red-500/30 bg-red-500/15 text-red-400' => $outcome === 0,
            ])>{{ $outcome }}</span>
        @empty
            <span class="text-xs italic text-zinc-600">Waiting for master trades…</span>
        @endforelse
    </div>
</div>
