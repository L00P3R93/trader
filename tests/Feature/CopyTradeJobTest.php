<?php

use App\Jobs\CopyTradeJob;
use App\Models\CopySetting;
use App\Models\CopyTrade;
use App\Models\DerivConnection;
use App\Models\User;
use App\Services\DerivApiService;

$masterTrade = [
    'transaction_id' => 111111,
    'contract_type' => 'CALL',
    'underlying' => 'R_50',
    'duration' => 5,
    'duration_unit' => 't',
    'buy_price' => 2.50,
];

function makeMaster(): DerivConnection
{
    return DerivConnection::factory()->master()->create();
}

function makeFollower(DerivConnection $master, array $settingOverrides = []): CopySetting
{
    $followerUser = User::factory()->create();
    DerivConnection::factory()->create(['user_id' => $followerUser->id, 'type' => 'follower']);

    return CopySetting::factory()->create(array_merge([
        'user_id' => $followerUser->id,
        'master_connection_id' => $master->id,
        'pattern_enabled' => false,
        'is_active' => true,
        'is_running' => true,
        'stake' => 1.00,
        'follow_master_stake' => false,
    ], $settingOverrides));
}

test('places a trade and records CopyTrade for an active follower', function () use ($masterTrade) {
    $master = makeMaster();
    makeFollower($master);

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldReceive('buyContract')
        ->once()
        ->andReturn(['buy' => ['transaction_id' => 99999]]);

    CopyTradeJob::dispatchSync($master->id, $masterTrade);

    $this->assertDatabaseHas('copy_trades', [
        'master_connection_id' => $master->id,
        'master_trx_id' => 111111,
        'follower_trx_id' => 99999,
        'symbol' => 'R_50',
        'contract_type' => 'CALL',
    ]);
});

test('uses follower stake when follow_master_stake is false', function () use ($masterTrade) {
    $master = makeMaster();
    makeFollower($master, ['stake' => 3.50, 'follow_master_stake' => false]);

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldReceive('buyContract')->once()->andReturn(['buy' => ['transaction_id' => 1]]);

    CopyTradeJob::dispatchSync($master->id, $masterTrade);

    $this->assertDatabaseHas('copy_trades', ['master_connection_id' => $master->id, 'stake' => 3.50]);
});

test('uses master buy_price as stake when follow_master_stake is true', function () use ($masterTrade) {
    $master = makeMaster();
    makeFollower($master, ['stake' => 1.00, 'follow_master_stake' => true]);

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldReceive('buyContract')->once()->andReturn(['buy' => ['transaction_id' => 1]]);

    CopyTradeJob::dispatchSync($master->id, $masterTrade);

    $this->assertDatabaseHas('copy_trades', ['master_connection_id' => $master->id, 'stake' => 2.50]);
});

test('skips follower with expired token', function () use ($masterTrade) {
    $master = makeMaster();
    $followerUser = User::factory()->create();
    DerivConnection::factory()->expired()->create(['user_id' => $followerUser->id, 'type' => 'follower']);
    CopySetting::factory()->create([
        'user_id' => $followerUser->id,
        'master_connection_id' => $master->id,
        'is_active' => true,
        'is_running' => true,
        'pattern_enabled' => false,
    ]);

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldNotReceive('buyContract');

    CopyTradeJob::dispatchSync($master->id, $masterTrade);

    $this->assertDatabaseMissing('copy_trades', ['master_connection_id' => $master->id]);
});

test('skips follower with inactive settings', function () use ($masterTrade) {
    $master = makeMaster();
    makeFollower($master, ['is_active' => false]);

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldNotReceive('buyContract');

    CopyTradeJob::dispatchSync($master->id, $masterTrade);

    $this->assertDatabaseMissing('copy_trades', ['master_connection_id' => $master->id]);
});

test('skips follower when pattern does not match', function () use ($masterTrade) {
    $master = makeMaster();
    $setting = makeFollower($master, [
        'pattern_enabled' => true,
        'follower_pattern' => '111',
    ]);

    // 2 wins then 1 loss — pattern "110" does not match "111"
    foreach ([true, true, false] as $isWin) {
        CopyTrade::factory()->create([
            'user_id' => $setting->user_id,
            'master_connection_id' => $master->id,
            'is_win' => $isWin,
            'traded_at' => now()->subMinutes(rand(1, 60)),
        ]);
    }

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldNotReceive('buyContract');

    CopyTradeJob::dispatchSync($master->id, $masterTrade);

    // No new (open) trade should be created
    $this->assertDatabaseMissing('copy_trades', [
        'master_connection_id' => $master->id,
        'is_win' => null,
    ]);
});

test('skips follower when traded symbol is filtered out', function () use ($masterTrade) {
    $master = makeMaster();
    makeFollower($master, [
        'filter_markets' => ['1HZ100V', '1HZ10V'], // R_50 not in list
    ]);

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldNotReceive('buyContract');

    CopyTradeJob::dispatchSync($master->id, $masterTrade);

    $this->assertDatabaseMissing('copy_trades', ['master_connection_id' => $master->id]);
});

test('logs error and continues when buyContract throws', function () use ($masterTrade) {
    $master = makeMaster();
    makeFollower($master);

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldReceive('buyContract')
        ->once()
        ->andThrow(new Exception('WS unavailable'));

    // Should not throw — errors are caught per-follower
    CopyTradeJob::dispatchSync($master->id, $masterTrade);

    $this->assertDatabaseMissing('copy_trades', ['master_connection_id' => $master->id]);
});
