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
        Schema::create('copy_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_connection_id')->constrained('deriv_connections')->cascadeOnDelete();
            $table->string('follower_trx_id')->nullable()->index();
            $table->string('master_trx_id')->nullable()->index();
            $table->string('symbol')->nullable();
            $table->string('contract_type')->nullable();
            $table->string('duration')->nullable();
            $table->string('barrier')->nullable();
            $table->decimal('stake', 10, 2)->default(0);
            $table->decimal('payout', 10, 2)->nullable();
            $table->decimal('profit', 10, 2)->nullable();
            $table->boolean('sell_at_market')->default(false);
            $table->boolean('is_win')->nullable();
            $table->timestamp('traded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('copy_trades');
    }
};
