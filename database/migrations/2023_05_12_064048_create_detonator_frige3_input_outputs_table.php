<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetonatorFrige3InputOutputsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detonator_frige3_input_outputs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('output_id')->nullable();
            $table->foreign('output_id')->references('id')->on('detonator_frige3_outputs')->onDelete('cascade');

            $table->unsignedBigInteger('input_id')->nullable();
            $table->foreign('input_id')->references('id')->on('detonator_frige3_details')->onDelete('cascade');

            $table->float('weight')->nullable();
            $table->integer('amount')->nullable();

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
        Schema::dropIfExists('detonator_frige3_input_outputs');
    }
}
