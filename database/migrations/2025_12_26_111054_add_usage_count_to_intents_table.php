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
        Schema::table('intents', function (Blueprint $table) {
            $table->integer('usage_count')->default(0)->after('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intents', function (Blueprint $table) {
            $table->dropColumn('usage_count');
        });
    }
};
