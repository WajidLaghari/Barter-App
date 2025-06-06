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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('fcm_token')->nullable()->after('email');
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('profile_picture');
            $table->tinyInteger('status')->default(1);
            $table->enum('role', ['admin','subAdmin', 'user']);
            $table->boolean('is_verified')->default(0);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
