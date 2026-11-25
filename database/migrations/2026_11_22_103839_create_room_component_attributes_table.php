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
        Schema::create('room_component_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_component_id')->constrained('room_components')->cascadeOnDelete();
            $table->string('name');
            $table->string('value');
            $table->string('unit', 50)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_component_attributes');
    }
};
