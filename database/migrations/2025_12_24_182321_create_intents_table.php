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
        Schema::create('intents', function (Blueprint $table) {
            $table->id();
            $table->string('dialogflow_id')->nullable()->unique(); // ID dari Dialogflow
            $table->string('display_name')->unique(); // Nama intent
            $table->text('description')->nullable(); // Deskripsi intent
            $table->integer('priority')->default(500000); // Priority
            $table->boolean('is_fallback')->default(false); // Apakah fallback intent
            $table->json('training_phrases')->nullable(); // Training phrases dalam format JSON
            $table->json('responses')->nullable(); // Responses dalam format JSON
            $table->json('parameters')->nullable(); // Parameters dalam format JSON
            $table->boolean('webhook_enabled')->default(false); // Apakah menggunakan webhook
            $table->string('action')->nullable(); // Action name
            $table->boolean('synced')->default(false); // Status sinkronisasi dengan Dialogflow
            $table->timestamp('last_synced_at')->nullable(); // Waktu terakhir sync
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intents');
    }
};
