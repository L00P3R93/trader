<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public function unlinkGoogle(): void
    {
        $user = Auth::user();
        $user->google_id = null;
        $user->avatar = null;
        $user->save();
    }
}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <flux:heading>{{ __('Google Account') }}</flux:heading>
        <flux:subheading>{{ __('Link your Google account for quick sign-in') }}</flux:subheading>
    </div>

    @if (session('status') === 'google-linked')
        <flux:text class="font-medium text-green-600 dark:text-green-400">
            {{ __('Google account linked successfully.') }}
        </flux:text>
    @endif

    @if ($errors->has('google'))
        <flux:text class="font-medium text-red-600 dark:text-red-400">
            {{ $errors->first('google') }}
        </flux:text>
    @endif

    @if (auth()->user()->google_id)
        <div class="flex items-center gap-4">
            <flux:text class="font-medium text-green-600 dark:text-green-400">
                {{ __('Google account linked') }} ✓
            </flux:text>

            <flux:button wire:click="unlinkGoogle" wire:loading.attr="disabled" variant="ghost" size="sm">
                <wire:loading wire:target="unlinkGoogle">{{ __('Unlinking…') }}</wire:loading>
                <wire:loading.remove wire:target="unlinkGoogle">{{ __('Unlink') }}</wire:loading.remove>
            </flux:button>
        </div>
    @else
        <flux:button href="{{ route('auth.google.link') }}">
            {{ __('Link your Google account') }}
        </flux:button>
    @endif
</section>
