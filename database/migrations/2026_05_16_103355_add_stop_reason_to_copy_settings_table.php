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
            $table->string('stop_reason')->nullable()->after('session_started_at');
            $table->decimal('stopped_at_profit', 12, 2)->nullable()->after('stop_reason');
        });
    }

    public function down(): void
    {
        Schema::table('copy_settings', function (Blueprint $table) {
            $table->dropColumn(['stop_reason', 'stopped_at_profit']);
        });
    }
};
