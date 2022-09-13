<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePivotLocationLineAndLocationStationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pivot_location_line_and_location_stations', function (Blueprint $table) {
            $table->integer('location_line_id');
            $table->integer('location_station_id');
            $table->primary(['location_line_id', 'location_station_id',]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pivot_location_line_and_location_stations');
    }
}
