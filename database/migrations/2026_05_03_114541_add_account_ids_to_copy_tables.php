<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deriv_connections', function (Blueprint $table) {
            $table->string('master_account_id')->nullable()->after('type');
        });

        Schema::table('copy_settings', function (Blueprint $table) {
            $table->string('follower_account_id')->nullable()->after('master_connection_id');
        });
    }

    public function down(): void
    {
        Schema::table('deriv_connections', function (Blueprint $table) {
            $table->dropColumn('master_account_id');
        });

        Schema::table('copy_settings', function (Blueprint $table) {
            $table->dropColumn('follower_account_id');
        });
    }
};
