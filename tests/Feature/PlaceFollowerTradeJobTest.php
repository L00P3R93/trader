<?php

use App\Jobs\PlaceFollowerTradeJob;
use App\Jobs\SettleCopyTradeJob;
use App\Models\DerivConnection;
use App\Models\User;
use App\Services\DerivApiService;
use Illuminate\Support\Facades\Bus;

$masterTrade = [
    'transaction_id' => 111111,
    'contract_type' => 'CALL',
    'underlying' => 'R_50',
    'symbol' => 'R_50',
    'duration' => 5,
    'duration_unit' => 't',
];

function makeFollowerConnection(): DerivConnection
{
    $user = User::factory()->create();

    return DerivConnection::factory()->create(['user_id' => $user->id, 'type' => 'follower']);
}

function makeJob(DerivConnection $conn, int $masterConnectionId, array $trade, float $stake = 1.00, array $overrides = []): PlaceFollowerTradeJob
{
    return new PlaceFollowerTradeJob(
        followerConnectionId: $conn->id,
        masterConnectionId: $masterConnectionId,
        masterTrade: $trade,
        stake: $stake,
        userId: $conn->user_id,
        followerAccountId: null,
        markPatternConsumed: $overrides['markPatternConsumed'] ?? false,
        markWaitTrigger: $overrides['markWaitTrigger'] ?? false,
    );
}

test('places a trade and records CopyTrade', function () use ($masterTrade) {
    Bus::fake([SettleCopyTradeJob::class]);

    $masterConn = DerivConnection::factory()->master()->create();
    $followerConn = makeFollowerConnection();

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldReceive('buyContract')
        ->once()
        ->andReturn(['buy' => ['transaction_id' => 99999, 'contract_id' => 'CTR-001']]);

    makeJob($followerConn, $masterConn->id, $masterTrade)->handle(app(DerivApiService::class));

    $this->assertDatabaseHas('copy_trades', [
        'user_id' => $followerConn->user_id,
        'master_connection_id' => $masterConn->id,
        'master_trx_id' => 111111,
        'follower_trx_id' => 99999,
        'symbol' => 'R_50',
        'contract_type' => 'CALL',
        'stake' => 1.00,
    ]);
});

test('dispatches SettleCopyTradeJob when contract_id is present', function () use ($masterTrade) {
    Bus::fake([SettleCopyTradeJob::class]);

    $masterConn = DerivConnection::factory()->master()->create();
    $followerConn = makeFollowerConnection();

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldReceive('buyContract')
        ->once()
        ->andReturn(['buy' => ['transaction_id' => 1, 'contract_id' => 'CTR-XYZ']]);

    makeJob($followerConn, $masterConn->id, $masterTrade)->handle(app(DerivApiService::class));

    Bus::assertDispatched(SettleCopyTradeJob::class, fn ($job) => $job->contractId === 'CTR-XYZ');
});

test('does not dispatch SettleCopyTradeJob when contract_id is absent', function () use ($masterTrade) {
    Bus::fake([SettleCopyTradeJob::class]);

    $masterConn = DerivConnection::factory()->master()->create();
    $followerConn = makeFollowerConnection();

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldReceive('buyContract')
        ->once()
        ->andReturn(['buy' => ['transaction_id' => 1]]);

    makeJob($followerConn, $masterConn->id, $masterTrade)->handle(app(DerivApiService::class));

    Bus::assertNotDispatched(SettleCopyTradeJob::class);
});

test('skips and logs when follower connection is expired', function () use ($masterTrade) {
    Bus::fake([SettleCopyTradeJob::class]);

    $user = User::factory()->create();
    $expiredConn = DerivConnection::factory()->expired()->create(['user_id' => $user->id, 'type' => 'follower']);
    $masterConn = DerivConnection::factory()->master()->create();

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldNotReceive('buyContract');

    makeJob($expiredConn, $masterConn->id, $masterTrade)->handle(app(DerivApiService::class));

    $this->assertDatabaseMissing('copy_trades', ['user_id' => $user->id]);
});

test('logs error and does not create CopyTrade when buyContract throws', function () use ($masterTrade) {
    Bus::fake([SettleCopyTradeJob::class]);

    $masterConn = DerivConnection::factory()->master()->create();
    $followerConn = makeFollowerConnection();

    $mock = $this->mock(DerivApiService::class);
    $mock->shouldReceive('buyContract')
        ->once()
        ->andThrow(new Exception('WS unavailable'));

    makeJob($followerConn, $masterConn->id, $masterTrade)->handle(app(DerivApiService::class));

    $this->assertDatabaseMissing('copy_trades', ['user_id' => $followerConn->user_id]);
});
