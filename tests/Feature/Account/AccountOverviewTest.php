<?php

use App\Exceptions\DerivApiException;
use App\Livewire\Account\Overview;
use App\Models\DerivConnection;
use App\Models\User;
use App\Services\DerivApiService;
use Livewire\Livewire;

test('account page requires authentication', function () {
    $this->get(route('account'))->assertRedirect(route('login'));
});

test('authenticated users can visit the account page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('account'))
        ->assertSuccessful();
});

test('account overview shows connect prompt when no deriv connection', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Overview::class)
        ->assertSee('No Deriv account connected');
});

test('account overview shows accounts from rest api', function () {
    $user = User::factory()->create();
    DerivConnection::factory()->create(['user_id' => $user->id]);

    $mock = Mockery::mock(DerivApiService::class);
    $mock->shouldReceive('getAccounts')
        ->once()
        ->andReturn([
            ['account_id' => 'CR12345', 'currency' => 'USD', 'is_demo' => false, 'balance' => 5000.00],
            ['account_id' => 'VRTC12345', 'currency' => 'USD', 'is_demo' => true, 'balance' => 10000.00],
        ]);
    $mock->shouldReceive('getCfdAccounts')->andReturn([]);
    $mock->shouldReceive('getBalance')
        ->andReturn(['balance' => ['balance' => 5000.00, 'currency' => 'USD', 'loginid' => 'CR12345']]);

    app()->instance(DerivApiService::class, $mock);

    Livewire::actingAs($user)
        ->test(Overview::class)
        ->assertSee('CR12345')
        ->assertSee('VRTC12345')
        ->assertSee('Real Accounts')
        ->assertSee('Demo Accounts');
});

test('account overview sets api error when rest call fails', function () {
    $user = User::factory()->create();
    DerivConnection::factory()->create(['user_id' => $user->id]);

    $mock = Mockery::mock(DerivApiService::class);
    $mock->shouldReceive('getAccounts')
        ->once()
        ->andThrow(new DerivApiException('Token expired or invalid. Please reconnect your Deriv account.'));
    $mock->shouldReceive('getCfdAccounts')->andReturn([]);
    $mock->shouldReceive('getBalance')->andReturn([]);

    app()->instance(DerivApiService::class, $mock);

    $component = Livewire::actingAs($user)->test(Overview::class);

    expect($component->get('apiError'))->toBe('Token expired or invalid. Please reconnect your Deriv account.');
    $component->assertSee('reconnect your Deriv account');
});

test('account overview separates demo and real accounts', function () {
    $user = User::factory()->create();
    DerivConnection::factory()->create(['user_id' => $user->id]);

    $mock = Mockery::mock(DerivApiService::class);
    $mock->shouldReceive('getAccounts')
        ->andReturn([
            ['account_id' => 'CR111', 'currency' => 'USD', 'is_demo' => false],
            ['account_id' => 'VRTC222', 'currency' => 'USD', 'is_demo' => true],
            ['account_id' => 'CR333', 'currency' => 'EUR', 'is_demo' => false],
        ]);
    $mock->shouldReceive('getCfdAccounts')->andReturn([]);
    $mock->shouldReceive('getBalance')->andReturn([]);

    app()->instance(DerivApiService::class, $mock);

    $component = Livewire::actingAs($user)->test(Overview::class);

    expect($component->instance()->realAccounts)->toHaveCount(2)
        ->and($component->instance()->demoAccounts)->toHaveCount(1);
});

test('account overview merges cfd accounts with options accounts', function () {
    $user = User::factory()->create();
    DerivConnection::factory()->create(['user_id' => $user->id]);

    $mock = Mockery::mock(DerivApiService::class);
    $mock->shouldReceive('getAccounts')
        ->andReturn([
            ['account_id' => 'CR111', 'currency' => 'USD', 'is_demo' => false, 'product_type' => 'options'],
        ]);
    $mock->shouldReceive('getCfdAccounts')
        ->andReturn([
            ['account_id' => 'MTR12345', 'currency' => 'USD', 'is_demo' => false, 'product_type' => 'cfd', 'landing_company_name' => 'MT5 Financial'],
            ['account_id' => 'MTD99999', 'currency' => 'USD', 'is_demo' => true, 'product_type' => 'cfd', 'landing_company_name' => 'MT5 Synthetic'],
        ]);
    $mock->shouldReceive('getBalance')->andReturn([]);

    app()->instance(DerivApiService::class, $mock);

    $component = Livewire::actingAs($user)->test(Overview::class);

    expect($component->instance()->realAccounts)->toHaveCount(2)
        ->and($component->instance()->demoAccounts)->toHaveCount(1);

    $component->assertSee('MTR12345')->assertSee('MTD99999')->assertSee('CFD');
});

test('reset demo balance calls service and shows success', function () {
    $user = User::factory()->create();
    DerivConnection::factory()->create(['user_id' => $user->id]);

    $mock = Mockery::mock(DerivApiService::class);
    $mock->shouldReceive('getAccounts')->andReturn([]);
    $mock->shouldReceive('getCfdAccounts')->andReturn([]);
    $mock->shouldReceive('getBalance')->andReturn([]);
    $mock->shouldReceive('resetDemoBalance')
        ->once()
        ->with(Mockery::type(DerivConnection::class), 'VRTC12345');
    $mock->shouldReceive('clearCache')->andReturn(null);

    app()->instance(DerivApiService::class, $mock);

    Livewire::actingAs($user)
        ->test(Overview::class)
        ->call('resetDemoBalance', 'VRTC12345')
        ->assertSet('resetSuccess', 'Demo balance reset to $10,000 successfully.');
});

test('refresh clears cache', function () {
    $user = User::factory()->create();
    DerivConnection::factory()->create(['user_id' => $user->id]);

    $mock = Mockery::mock(DerivApiService::class);
    $mock->shouldReceive('clearCache')->once();
    $mock->shouldReceive('getAccounts')->andReturn([]);
    $mock->shouldReceive('getCfdAccounts')->andReturn([]);
    $mock->shouldReceive('getBalance')->andReturn([]);

    app()->instance(DerivApiService::class, $mock);

    Livewire::actingAs($user)
        ->test(Overview::class)
        ->call('refresh');
});
