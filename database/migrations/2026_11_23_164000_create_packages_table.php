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
        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('showrooms_id')->constrained('showrooms')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('sku', 100)->nullable();
            $table->text('description');
            $table->string('image_path', 255)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('current_step')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['showrooms_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
