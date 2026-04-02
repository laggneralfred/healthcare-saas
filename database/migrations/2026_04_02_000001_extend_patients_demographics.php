<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the enum check constraint so gender accepts any string value
        DB::statement('ALTER TABLE patients DROP CONSTRAINT IF EXISTS patients_gender_check');

        // Update existing lowercase enum values to capitalized display values
        DB::table('patients')->where('gender', 'male')->update(['gender' => 'Male']);
        DB::table('patients')->where('gender', 'female')->update(['gender' => 'Female']);
        DB::table('patients')->where('gender', 'other')->update(['gender' => 'Other']);
        DB::table('patients')->where('gender', 'prefer_not_to_say')->update(['gender' => 'Prefer not to say']);

        Schema::table('patients', function (Blueprint $table) {
            // Rename address → address_line_1
            $table->renameColumn('address', 'address_line_1');
        });

        Schema::table('patients', function (Blueprint $table) {
            // New name fields
            $table->string('middle_name')->nullable()->after('last_name');
            $table->string('preferred_name')->nullable()->after('middle_name');

            // Pronouns
            $table->string('pronouns', 50)->nullable()->after('gender');

            // Address
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('country', 100)->nullable()->default('USA')->after('postal_code');

            // Emergency contact
            $table->string('emergency_contact_name')->nullable()->after('country');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_phone');

            // Additional info
            $table->string('occupation')->nullable()->after('emergency_contact_relationship');
            $table->string('referred_by')->nullable()->after('occupation');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->renameColumn('address_line_1', 'address');
            $table->dropColumn([
                'middle_name',
                'preferred_name',
                'pronouns',
                'address_line_2',
                'country',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relationship',
                'occupation',
                'referred_by',
            ]);
        });

        // Revert gender back to enum (drop and re-add)
        DB::statement("ALTER TABLE patients ALTER COLUMN gender TYPE VARCHAR(50)");
    }
};
