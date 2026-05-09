<div>
    @if($showTutorial)
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-show="$wire.showTutorial"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

            {{-- Tutorial Card --}}
            <div
                class="relative z-10 w-full max-w-lg rounded-2xl border border-[#1F2937] bg-[#0B1220] shadow-2xl"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            >
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-[#1F2937] px-6 py-4">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full bg-[#CDF12B]"></div>
                        <span class="text-xs font-medium tracking-widest text-zinc-500 uppercase">Platform Tour</span>
                    </div>
                    <button
                        wire:click="skip"
                        class="rounded-lg px-3 py-1 text-xs font-medium text-zinc-500 transition hover:bg-zinc-800 hover:text-zinc-300"
                    >
                        Skip tour
                    </button>
                </div>

                {{-- Step Content --}}
                <div class="px-8 py-8">
                    {{-- Icon & Badge --}}
                    <div class="mb-6 flex flex-col items-center text-center">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-[#1E45FC]/10 ring-1 ring-[#1E45FC]/20">
                            <flux:icon
                                :name="$step['icon']"
                                class="size-8 text-[#1E45FC]"
                            />
                        </div>
                        <span class="mb-2 rounded-full bg-[#1E45FC]/10 px-3 py-0.5 text-xs font-semibold tracking-wide text-[#1E45FC] uppercase">
                            {{ $step['badge'] }}
                        </span>
                        <h2 class="mt-2 text-xl font-bold text-white">
                            {{ $step['title'] }}
                        </h2>
                    </div>

                    {{-- Description --}}
                    <p class="mb-5 text-center text-sm leading-relaxed text-zinc-400">
                        {{ $step['description'] }}
                    </p>

                    {{-- Tip --}}
                    @if(isset($step['tip']))
                        <div class="mb-6 flex items-start gap-3 rounded-xl border border-[#1F2937] bg-zinc-900/60 px-4 py-3">
                            <flux:icon.light-bulb class="mt-0.5 size-4 shrink-0 text-[#CDF12B]" />
                            <p class="text-xs leading-relaxed text-zinc-400">{{ $step['tip'] }}</p>
                        </div>
                    @endif

                    {{-- Optional Actions --}}
                    @if(isset($step['action_label']))
                        <div class="mb-6 flex flex-wrap items-center justify-center gap-2">
                            @if(isset($step['action_url']))
                                <flux:button
                                    href="{{ $step['action_url'] }}"
                                    target="_blank"
                                    rel="noopener"
                                    variant="primary"
                                    size="sm"
                                    icon-trailing="arrow-top-right-on-square"
                                >
                                    {{ $step['action_label'] }}
                                </flux:button>
                            @elseif(isset($step['action_route']))
                                <flux:button
                                    href="{{ route($step['action_route']) }}"
                                    variant="primary"
                                    size="sm"
                                    icon-trailing="arrow-right"
                                >
                                    {{ $step['action_label'] }}
                                </flux:button>
                            @endif

                            @if(isset($step['action_secondary_label'], $step['action_route']))
                                <flux:button
                                    href="{{ route($step['action_route']) }}"
                                    variant="ghost"
                                    size="sm"
                                >
                                    {{ $step['action_secondary_label'] }}
                                </flux:button>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Progress Steps --}}
                <div class="flex items-center justify-center gap-1.5 pb-6">
                    @for($i = 1; $i <= $totalSteps; $i++)
                        <button
                            wire:click="$set('currentStep', {{ $i }})"
                            @class([
                                'h-1.5 rounded-full transition-all duration-300',
                                'w-6 bg-[#1E45FC]' => $currentStep === $i,
                                'w-1.5 bg-zinc-700 hover:bg-zinc-500' => $currentStep !== $i,
                            ])
                            aria-label="Step {{ $i }}"
                        ></button>
                    @endfor
                </div>

                {{-- Footer Navigation --}}
                <div class="flex items-center justify-between border-t border-[#1F2937] px-6 py-4">
                    <flux:button
                        wire:click="previousStep"
                        variant="ghost"
                        size="sm"
                        icon="arrow-left"
                        @class(['invisible' => $currentStep === 1])
                    >
                        Back
                    </flux:button>

                    <span class="text-xs text-zinc-600">{{ $currentStep }} / {{ $totalSteps }}</span>

                    <flux:button
                        wire:click="nextStep"
                        variant="primary"
                        size="sm"
                        icon-trailing="{{ $currentStep === $totalSteps ? 'check' : 'arrow-right' }}"
                        wire:loading.attr="disabled"
                        wire:target="nextStep"
                    >
                        @if($currentStep === $totalSteps)
                            Get Started
                        @else
                            Next
                        @endif
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
