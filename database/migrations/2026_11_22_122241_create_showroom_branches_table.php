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
        Schema::create('showroom_branches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('showrooms_id')->constrained('showrooms')->cascadeOnDelete();
            $table->string('name');
            $table->string('city', 100);
            $table->text('address');
            $table->string('phone', 50);
            $table->string('whatsapp', 50)->nullable();
            $table->boolean('is_main')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showroom_branches');
    }
};
