<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasTable('commands')) {
            Schema::create('commands', function (Blueprint $table) {
                $table->id();
                $table->string('category');
                $table->string('command_name');
                $table->string('command_description')->nullable();
                $table->integer('cooldown');
                $table->integer('public')->default(1);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commands');
    }
};
