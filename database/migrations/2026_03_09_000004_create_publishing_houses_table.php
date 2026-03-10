<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('publishing_houses')) return;

        Schema::create('publishing_houses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('website')->nullable();
            $table->year('founded_year')->nullable();
            $table->string('country', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('logo', 500)->nullable();
            $table->string('api_source', 50)->nullable();
            $table->string('api_id', 191)->nullable();
            $table->timestamp('api_last_updated')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('country', 'idx_country');
            $table->index('status', 'idx_status');
            $table->index(['api_source', 'api_id'], 'idx_api_source_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publishing_houses');
    }
};
