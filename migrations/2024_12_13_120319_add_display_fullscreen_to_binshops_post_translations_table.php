<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDisplayFullscreenToBinshopsPostTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('binshopsblog.db_connection') ?? config('database.default'))->table('binshops_post_translations', function (Blueprint $table) {
            $table->boolean("display_fullscreen")->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection(config('binshopsblog.db_connection') ?? config('database.default'))->table('binshops_post_translations', function (Blueprint $table) {
            $table->dropColumn("display_fullscreen");
        });
    }
}
