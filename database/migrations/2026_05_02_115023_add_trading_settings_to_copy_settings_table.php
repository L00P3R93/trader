<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('copy_settings', function (Blueprint $table) {
            $table->dropColumn('min_consecutive_wins');

            $table->string('follower_pattern', 50)->default('111')->after('master_connection_id');
            $table->boolean('pattern_enabled')->default(true)->after('follower_pattern');
            $table->decimal('stake', 10, 2)->default(1.00)->after('pattern_enabled');
            $table->boolean('follow_master_stake')->default(false)->after('stake');
            $table->boolean('safe_mode')->default(false)->after('follow_master_stake');
            $table->decimal('stake_multiplier', 10, 2)->default(1.00)->after('safe_mode');
            $table->decimal('take_profit', 10, 2)->nullable()->after('stake_multiplier');
            $table->decimal('stop_loss', 10, 2)->nullable()->after('take_profit');
            $table->unsignedTinyInteger('max_compound')->default(0)->after('stop_loss');
            $table->unsignedTinyInteger('do_martingale_at')->default(1)->after('max_compound');
            $table->unsignedTinyInteger('max_martingale')->default(0)->after('do_martingale_at');
            $table->string('if_hit_max_martingale', 10)->default('stop')->after('max_martingale');
            $table->unsignedTinyInteger('wait_for_loss')->default(0)->after('if_hit_max_martingale');
            $table->boolean('only_use_1x_wait_for_loss')->default(false)->after('wait_for_loss');
            $table->json('filter_markets')->nullable()->after('only_use_1x_wait_for_loss');
            $table->json('synthetic_indices')->nullable()->after('filter_markets');
            $table->json('forex_pairs')->nullable()->after('synthetic_indices');
            $table->boolean('is_running')->default(false)->after('forex_pairs');
            $table->decimal('start_balance', 15, 2)->nullable()->after('is_running');
        });
    }

    public function down(): void
    {
        Schema::table('copy_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_consecutive_wins')->default(1);

            $table->dropColumn([
                'follower_pattern', 'pattern_enabled', 'stake', 'follow_master_stake',
                'safe_mode', 'stake_multiplier', 'take_profit', 'stop_loss',
                'max_compound', 'do_martingale_at', 'max_martingale', 'if_hit_max_martingale',
                'wait_for_loss', 'only_use_1x_wait_for_loss', 'filter_markets',
                'synthetic_indices', 'forex_pairs', 'is_running', 'start_balance',
            ]);
        });
    }
};
