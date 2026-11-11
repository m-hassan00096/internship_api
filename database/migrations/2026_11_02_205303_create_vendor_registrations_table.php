<?php

use App\Enums\RegistrationStatus;
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
        Schema::create('vendor_registrations', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_number')->unique();
            $table->string('vendor_name');
            $table->foreignId('business_type_id')->constrained('business_types')->restrictOnDelete();
            $table->string('contact_name');
            $table->string('email')->index();
            $table->string('phone')->index();
            $table->boolean('has_whatsapp')->default(false);
            $table->unsignedTinyInteger('status')->default(RegistrationStatus::NEW->value)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_registrations');
    }
};
