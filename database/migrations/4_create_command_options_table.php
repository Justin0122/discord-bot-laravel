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
        if (!Schema::hasTable('command_options')) {
            Schema::create('command_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('command_id')->references('id')->on('commands')->onDelete('cascade');
                $table->string('name');
                $table->string('description')->nullable();
                $table->boolean('required')->default(false);
                $table->integer('type');
                $table->json('choices')->nullable();
                $table->json('options')->nullable();
                $table->json('channel_types')->nullable();
                $table->integer('min_value')->nullable();
                $table->integer('max_value')->nullable();
                $table->integer('min_length')->nullable();
                $table->integer('max_length')->nullable();
                $table->boolean('autocomplete')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_options');
    }
};
