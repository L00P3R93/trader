<?php

use App\Livewire\Onboarding\Tutorial;
use App\Models\User;
use Livewire\Livewire;

it('shows tutorial to new users who have not completed onboarding', function () {
    $user = User::factory()->create(['onboarding_completed_at' => null]);

    Livewire::actingAs($user)
        ->test(Tutorial::class)
        ->assertSet('showTutorial', true)
        ->assertSet('currentStep', 1);
});

it('does not show tutorial to users who have already completed onboarding', function () {
    $user = User::factory()->create(['onboarding_completed_at' => now()]);

    Livewire::actingAs($user)
        ->test(Tutorial::class)
        ->assertSet('showTutorial', false);
});

it('advances to the next step', function () {
    $user = User::factory()->create(['onboarding_completed_at' => null]);

    Livewire::actingAs($user)
        ->test(Tutorial::class)
        ->assertSet('currentStep', 1)
        ->call('nextStep')
        ->assertSet('currentStep', 2);
});

it('goes back to the previous step', function () {
    $user = User::factory()->create(['onboarding_completed_at' => null]);

    Livewire::actingAs($user)
        ->test(Tutorial::class)
        ->set('currentStep', 3)
        ->call('previousStep')
        ->assertSet('currentStep', 2);
});

it('does not go below step 1 when on the first step', function () {
    $user = User::factory()->create(['onboarding_completed_at' => null]);

    Livewire::actingAs($user)
        ->test(Tutorial::class)
        ->assertSet('currentStep', 1)
        ->call('previousStep')
        ->assertSet('currentStep', 1);
});

it('skips onboarding and marks it as complete', function () {
    $user = User::factory()->create(['onboarding_completed_at' => null]);

    Livewire::actingAs($user)
        ->test(Tutorial::class)
        ->call('skip')
        ->assertSet('showTutorial', false);

    expect($user->fresh()->onboarding_completed_at)->not->toBeNull();
});

it('completes onboarding when next is called on the last step', function () {
    $user = User::factory()->create(['onboarding_completed_at' => null]);

    $component = Livewire::actingAs($user)->test(Tutorial::class);
    $totalSteps = $component->instance()->totalSteps();

    $component
        ->set('currentStep', $totalSteps)
        ->call('nextStep')
        ->assertSet('showTutorial', false);

    expect($user->fresh()->onboarding_completed_at)->not->toBeNull();
});

it('returns the correct number of steps', function () {
    $user = User::factory()->create(['onboarding_completed_at' => null]);

    $component = Livewire::actingAs($user)->test(Tutorial::class);

    expect($component->instance()->totalSteps())->toBe(6);
});
