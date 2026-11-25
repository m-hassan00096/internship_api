<?php

use App\Enums\ContractStatus;
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
        Schema::create('showrooms_contract', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('showrooms_id')->constrained('showrooms')->cascadeOnDelete();
            $table->string('contract_number')->nullable()->unique();
            $table->date('started_at');
            $table->date('end_at');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->unsignedInteger('max_rooms')->default(0);
            $table->unsignedInteger('max_packages')->default(0);
            $table->string('contract_file')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('status')->default(ContractStatus::ACTIVE->value)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showrooms_contract');
    }
};
