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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('initiator_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->enum('status',['pending','completed','cancelled','disputed'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('disputed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
