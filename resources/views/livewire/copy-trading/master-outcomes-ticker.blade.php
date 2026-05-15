<div wire:poll.3s class="flex flex-wrap items-center gap-1.5">
    <span class="mr-1 text-xs font-medium uppercase tracking-wide text-zinc-500">Master:</span>
    @forelse($this->outcomes as $outcome)
        <span @class([
            'inline-flex h-6 w-6 items-center justify-center rounded font-mono text-xs font-bold',
            'border border-[#22C55E]/30 bg-[#22C55E]/15 text-[#22C55E]' => $outcome === 1,
            'border border-red-500/30 bg-red-500/15 text-red-400' => $outcome === 0,
        ])>{{ $outcome }}</span>
    @empty
        <span class="text-xs text-zinc-600 italic">Waiting for master trades…</span>
    @endforelse
</div>
