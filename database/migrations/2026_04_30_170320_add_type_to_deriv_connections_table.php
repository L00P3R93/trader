<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deriv_connections', function (Blueprint $table) {
            $table->enum('type', ['follower', 'master'])->default('follower')->after('scope');
        });
    }

    public function down(): void
    {
        Schema::table('deriv_connections', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
