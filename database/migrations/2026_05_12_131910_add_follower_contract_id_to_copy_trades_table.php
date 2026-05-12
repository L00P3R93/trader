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
        Schema::table('copy_trades', function (Blueprint $table) {
            $table->string('follower_contract_id')->nullable()->after('follower_trx_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('copy_trades', function (Blueprint $table) {
            $table->dropColumn('follower_contract_id');
        });
    }
};
