<?php

use App\Enums\VerificationStatus;
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
        Schema::create('showrooms_verification', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('showrooms_id')->constrained('showrooms')->cascadeOnDelete();
            $table->unsignedSmallInteger('month')->default(1);
            $table->decimal('monthly_price', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->unsignedTinyInteger('status')->default(VerificationStatus::PENDING->value)->index();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->foreignId('review_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('review_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showrooms_verification');
    }
};
