<?php

use App\Enums\RoomAvailabilityStatus;
use App\Enums\RoomStatus;
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
        Schema::create('rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('showrooms_id')->constrained('showrooms')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('name');
            $table->string('sku', 100)->nullable()->index();
            $table->string('warranty')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedTinyInteger('discount_type')->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->unsignedTinyInteger('availability_status')->default(RoomAvailabilityStatus::AVAILABLE->value)->index();
            $table->unsignedSmallInteger('preparation_days')->nullable();
            $table->unsignedTinyInteger('status')->default(RoomStatus::DRAFT->value)->index();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
