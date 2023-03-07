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
        Schema::dropIfExists('special_pool_events');
        Schema::create('special_pool_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('home_id');
            $table->foreign('home_id')->references('id')->on('teams');
            $table->unsignedBigInteger('away_id');
            $table->foreign('away_id')->references('id')->on('teams');
            $table->integer('home_result')->nullable();
            $table->integer('away_result')->nullable();
            $table->bigInteger('date');
            $table->foreignId('pool_id')->constrained();
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
        Schema::dropIfExists('special_pool_events');
    }
};
