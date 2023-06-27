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
        if (!Schema::hasTable('user_cooldowns')) {
            Schema::create('user_cooldowns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discord_id')->references('discord_id')->on('users')->onDelete('cascade');
                $table->foreignId('command_id')->constrained('commands');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_cooldowns');
    }
};
