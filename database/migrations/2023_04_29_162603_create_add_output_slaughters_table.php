<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddOutputSlaughtersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('output_slaughterSupervisors_details', function (Blueprint $table) {
            $table->id();
            $table->integer('weight');
            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')->references('id')->on('output_production_types')->onDelete('cascade');
            $table->boolean('direct_to_bahra')->default(0);
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
        Schema::dropIfExists('add_output_slaughters');
    }
}
