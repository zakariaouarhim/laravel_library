<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('authors')) return;

        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('biography')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('profile_image', 500)->nullable();
            $table->string('website')->nullable();
            $table->string('api_source', 50)->nullable();
            $table->string('api_id', 191)->nullable();
            $table->timestamp('api_last_updated')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('name', 'idx_name');
            $table->index('nationality', 'idx_nationality');
            $table->index('status', 'idx_status');
            $table->index(['api_source', 'api_id'], 'idx_api_source_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
