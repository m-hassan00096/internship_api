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
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('showrooms_id')->constrained('showrooms')->cascadeOnDelete();
            $table->string('reviewable_type', 50);
            $table->unsignedBigInteger('reviewable_id');
            $table->tinyInteger('rating');
            $table->text('comment')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['reviewable_type', 'reviewable_id']);
            $table->unique(['user_id', 'reviewable_type', 'reviewable_id'], 'user_reviewable_unique');
            $table->index(['showrooms_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
