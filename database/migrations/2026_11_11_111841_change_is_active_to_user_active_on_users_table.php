<?php

use App\Enums\UserActive;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('user_active')
                ->default(UserActive::ACTIVE->value)
                ->after('password')
                ->index();
        });

        DB::table('users')->update([
            'user_active' => DB::raw(
                'CASE WHEN is_active = 1 THEN '.UserActive::ACTIVE->value.' ELSE '.UserActive::INACTIVE->value.' END'
            ),
        ]);

        if (Schema::hasTable('vendor_registrations')) {
            $pendingVendorUserIds = DB::table('vendor_registrations')
                ->whereNotNull('user_id')
                ->pluck('user_id');

            if ($pendingVendorUserIds->isNotEmpty()) {
                DB::table('users')
                    ->whereIn('id', $pendingVendorUserIds)
                    ->where('user_active', UserActive::INACTIVE->value)
                    ->update(['user_active' => UserActive::PENDING_PASSWORD->value]);
            }
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('password');
        });

        DB::table('users')->update([
            'is_active' => DB::raw('CASE WHEN user_active = '.UserActive::ACTIVE->value.' THEN 1 ELSE 0 END'),
        ]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['user_active']);
            $table->dropColumn('user_active');
        });
    }
};
