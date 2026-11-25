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
        Schema::create('package_rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('packages_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('rooms_id')->constrained('rooms')->cascadeOnDelete();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['packages_id', 'rooms_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_rooms');
    }
};
