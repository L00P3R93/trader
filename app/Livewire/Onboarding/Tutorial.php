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
                'tip' => 'You\'ll need an active Deriv account. If you don\'t have one, sign up free at deriv.com.',
                'action_label' => 'Connect Deriv Account',
                'action_route' => 'deriv.connect',
            ],
            [
                'icon' => 'home',
                'badge' => 'Step 2',
                'title' => 'Explore Your Dashboard',
                'description' => 'Your dashboard shows your live account balance, recent trade performance, win rate, and P&L — all updating in real time from your Deriv connection.',
                'tip' => 'The status cards at the top give you a quick health check of your entire trading setup.',
            ],
            [
                'icon' => 'users',
                'badge' => 'Step 3',
                'title' => 'Choose a Master Trader',
                'description' => 'Browse our curated list of verified master traders. Each master has a public performance record. Select one whose strategy and risk profile matches your goals.',
                'tip' => 'You can switch masters at any time — your settings carry over.',
                'action_label' => 'Browse Masters',
                'action_route' => 'copy-trading',
            ],
            [
                'icon' => 'cog-6-tooth',
                'badge' => 'Step 4',
                'title' => 'Configure Your Bot',
                'description' => 'Set your stake amount, take-profit, stop-loss, and optional Martingale recovery. You can also filter which markets or instruments the bot copies.',
                'tip' => 'Start with a small stake on demo until you\'re confident in the master\'s strategy.',
            ],
            [
                'icon' => 'play',
                'badge' => 'Ready',
                'title' => 'You\'re All Set to Go Live!',
                'description' => 'Activate your copy bot and it will instantly mirror every trade your master makes — complete with your configured stake and risk controls.',
                'tip' => 'Monitor your Trade History page to track every copied position in real time.',
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
