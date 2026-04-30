@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="CopyTrade Pro" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-green-500 shadow-sm shadow-green-500/30">
            <x-app-logo-icon class="size-4 fill-current text-black" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="CopyTrade Pro" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-green-500 shadow-sm shadow-green-500/30">
            <x-app-logo-icon class="size-4 fill-current text-black" />
        </x-slot>
    </flux:brand>
@endif
