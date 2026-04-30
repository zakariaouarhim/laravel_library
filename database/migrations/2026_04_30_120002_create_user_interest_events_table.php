<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_interest_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('action', 16);
            $table->string('subject_type', 16);
            $table->unsignedBigInteger('subject_id');
            $table->tinyInteger('rating_value')->nullable();
            $table->timestamps();

            // Per-action dedup rules live in the service, not the schema.
            $table->index(
                ['user_id', 'action', 'subject_type', 'subject_id', 'created_at'],
                'uie_lookback'
            );
            $table->index(['user_id', 'created_at']);

            $table->foreign('user_id')->references('id')->on('user')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_interest_events');
    }
};
