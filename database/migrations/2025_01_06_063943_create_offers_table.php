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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('offered_item_id')->constrained('items')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('offered_by')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->text('message_text')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
