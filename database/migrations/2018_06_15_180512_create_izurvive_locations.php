<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIzurviveLocations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('izurvive_locations', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name_en');
            $table->string('name_ru');
            $table->string('latitude');
            $table->string('longitude');

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
        Schema::drop('izurvive_locations');
    }
}
