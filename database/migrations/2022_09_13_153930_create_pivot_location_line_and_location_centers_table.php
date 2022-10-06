<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePivotLocationLineAndLocationCentersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pivot_location_line_and_location_centers', function (Blueprint $table) {
            $table->string('location_line_uuid',36);
            $table->string('location_center_uuid',36);
            $table->primary(['location_line_uuid','location_center_uuid',],'pivot_location_line_and_location_centers__pk');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pivot_location_line_and_location_centers');
    }
}
