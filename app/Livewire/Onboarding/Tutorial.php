<?php

namespace App\Livewire\Onboarding;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Tutorial extends Component
{
    public bool $showTutorial = false;

    public int $currentStep = 1;

    public function mount(): void
    {
        $this->showTutorial = ! Auth::user()->hasCompletedOnboarding();
    }

    public function nextStep(): void
    {
        if ($this->currentStep < $this->totalSteps()) {
            $this->currentStep++;
        } else {
            $this->complete();
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function skip(): void
    {
        $this->complete();
    }

    public function complete(): void
    {
        Auth::user()->update(['onboarding_completed_at' => now()]);
        $this->showTutorial = false;
    }

    public function totalSteps(): int
    {
        return count($this->steps());
    }

    /** @return array<int, array<string, string>> */
    public function steps(): array
    {
        return [
            [
                'icon' => 'rocket-launch',
                'badge' => 'Welcome',
                'title' => 'Welcome to Copy Trader',
                'description' => 'You\'re about to unlock fully automated copy trading powered by the Deriv platform. This quick walkthrough will get you set up and earning in minutes.',
                'tip' => 'You can revisit this guide anytime from your account settings.',
            ],
            [
                'icon' => 'link',
                'badge' => 'Step 1',
                'title' => 'Connect Your Deriv Account',
                'description' => 'Everything starts with linking your Deriv account via OAuth. This gives the platform secure access to execute trades on your behalf — no password is ever stored.',
                'tip' => 'Don\'t have a Deriv account yet? Create one for free — it only takes a minute.',
                'action_label' => 'Create a Deriv Account',
                'action_url' => 'https://track.deriv.com/_Ed6zZUkRQYaX6ytsi48cKWNd7ZgqdRLk/1/',
                'action_secondary_label' => 'Connect Existing Account',
                'action_route' => 'deriv.connect',
            ],
            [
                'icon' => 'arrows-right-left',
                'badge' => 'Step 2',
                'title' => 'Set Up Copy Trading',
                'description' => 'Choose how to copy trade: follow a verified platform master, or use your own Deriv accounts. Self-copy lets you mirror trades between your demo and real accounts (e.g. demo → real).',
                'tip' => 'Self-copy is great for testing a strategy on demo first, then running it live on your real account automatically.',
                'action_label' => 'Go to Copy Trading',
                'action_route' => 'copy-trading',
            ],
            [
                'icon' => 'home',
                'badge' => 'Step 3',
                'title' => 'Explore Your Dashboard',
                'description' => 'Your dashboard shows your live account balance, recent trade performance, win rate, and P&L — all updating in real time from your Deriv connection.',
                'tip' => 'The status cards at the top give you a quick health check of your entire trading setup.',
            ],
            [
                'icon' => 'cog-6-tooth',
                'badge' => 'Step 4',
                'title' => 'Configure Your Bot',
                'description' => 'Set the follower pattern (the sequence of wins/losses that must occur before the bot copies), then use More Settings to configure your stake, take-profit, stop-loss, and Martingale options.',
                'tip' => 'Start with a small stake on a demo account until you\'re confident the setup works as expected.',
            ],
            [
                'icon' => 'play',
                'badge' => 'Ready',
                'title' => 'Hit Run — That\'s It!',
                'description' => 'Press the Run button on the Copy Trading page. The bot starts instantly and will mirror every qualifying trade automatically — no terminal, no manual steps required.',
                'tip' => 'Monitor your Trade History tab to track every copied position in real time.',
                'action_label' => 'Start Copy Trading',
                'action_route' => 'copy-trading',
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.onboarding.tutorial', [
            'steps' => $this->steps(),
            'totalSteps' => $this->totalSteps(),
            'step' => $this->steps()[$this->currentStep - 1],
        ]);
    }
}
