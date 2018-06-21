<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIzurviveLocationSpellings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('izurvive_location_spellings', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('location_id');
            $table->string('spelling');

            $table->timestamps();

            $table->foreign('location_id')
                  ->references('id')
                  ->on('izurvive_locations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('izurvive_location_spellings');
    }
}
