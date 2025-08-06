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
        Schema::create('shift_summaries', function (Blueprint $table) {
            $table->id();
            $table->dateTime('from');
            $table->dateTime('to');
            $table->string('shift_name'); // night, morning, evening

            $table->decimal('services', 10, 2)->default(0);
            $table->decimal('labs', 10, 2)->default(0);
            $table->decimal('doctor_payouts', 10, 2)->default(0);
            $table->decimal('expenses', 10, 2)->default(0);
            $table->decimal('returns', 10, 2)->default(0);
            $table->decimal('final_cash', 10, 2)->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_summaries');
    }
};
