@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="CopyTrade Pro" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-[#CDF12B] shadow-sm shadow-[#CDF12B]/30">
            <x-app-logo-icon class="size-4 fill-current text-[#0B1220]" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="CopyTrade Pro" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-[#CDF12B] shadow-sm shadow-[#CDF12B]/30">
            <x-app-logo-icon class="size-4 fill-current text-[#0B1220]" />
        </x-slot>
    </flux:brand>
@endif
