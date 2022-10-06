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
            $table->string('location_line_uuid',36);
            $table->string('location_station_uuid',36);
            $table->primary(['location_line_uuid', 'location_station_uuid',],'pivot_location_line_and_location_stations__pk');
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
