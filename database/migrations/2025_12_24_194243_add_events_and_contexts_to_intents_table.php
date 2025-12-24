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
            $table->json('events')->nullable()->after('training_phrases'); // Event names
            $table->json('input_contexts')->nullable()->after('parameters'); // Input contexts
            $table->json('output_contexts')->nullable()->after('input_contexts'); // Output contexts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intents', function (Blueprint $table) {
            $table->dropColumn(['events', 'input_contexts', 'output_contexts']);
        });
    }
};
