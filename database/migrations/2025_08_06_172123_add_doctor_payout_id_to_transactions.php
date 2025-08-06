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
        Schema::table('service_transactions', function (Blueprint $table) {
            $table->foreignId('doctor_payout_id')->nullable()->after('arrived')->constrained('doctor_payouts')->nullOnDelete();
        });

        Schema::table('lab_test_transactions', function (Blueprint $table) {
            $table->foreignId('doctor_payout_id')->nullable()->after('hospital_share')->constrained('doctor_payouts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_transactions', function (Blueprint $table) {
            $table->dropForeign(['doctor_payout_id']);
            $table->dropColumn('doctor_payout_id');
        });

        Schema::table('lab_test_transactions', function (Blueprint $table) {
            $table->dropForeign(['doctor_payout_id']);
            $table->dropColumn('doctor_payout_id');
        });
    }
};
