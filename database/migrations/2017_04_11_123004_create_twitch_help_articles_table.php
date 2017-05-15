<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTwitchHelpArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('twitch_help_articles', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->string('title')->nullable();
            $table->string('category_id')->nullable();
            $table->timestamp('published');

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
        Schema::drop('twitch_help_articles');
    }
}
