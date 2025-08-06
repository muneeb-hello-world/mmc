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
            $table->boolean('is_returned')->default(false);
        });

        Schema::table('lab_test_transactions', function (Blueprint $table) {
            $table->boolean('is_returned')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('service_transactions', function (Blueprint $table) {
            $table->dropColumn('is_returned');
        });

        Schema::table('lab_test_transactions', function (Blueprint $table) {
            $table->dropColumn('is_returned');
        });
    }
};
