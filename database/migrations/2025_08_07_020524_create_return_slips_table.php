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
        Schema::create('return_slips', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'service' or 'lab'
            $table->unsignedBigInteger('transaction_id');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('refunded_by')->nullable();
            $table->timestamps();
            $table->foreign('refunded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_slips');
    }
};
