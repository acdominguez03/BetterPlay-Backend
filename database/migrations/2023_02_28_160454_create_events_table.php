<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('events');
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('home_id');
            $table->foreign('home_id')->references('id')->on('teams');
            $table->unsignedBigInteger('away_id');
            $table->foreign('away_id')->references('id')->on('teams');
            $table->integer('home_result')->nullable();
            $table->integer('away_result')->nullable();
            $table->enum('winner', ['1','X','2'])->nullable();
            $table->float('home_odd');
            $table->float('away_odd');
            $table->float('tie_odd');
            $table->bigInteger('date');
            $table->bigInteger('finalDate');
            $table->enum('sport', ['soccer', 'basketball','tennis']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
};
