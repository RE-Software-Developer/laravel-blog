<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBinshopsConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('binshopsblog.db_connection') ?? config('database.default'))->create('binshops_configurations', function (Blueprint $table) {
            $table->string("key")->primary();
            $table->string("value");
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
        Schema::connection(config('binshopsblog.db_connection') ?? config('database.default'))->dropIfExists('binshops_configurations');
    }
}
