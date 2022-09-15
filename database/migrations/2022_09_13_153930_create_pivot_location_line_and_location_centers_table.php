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
            $table->integer('location_line_id');
            $table->integer('location_center_id');
            $table->primary(['location_line_id','location_center_id',],'pivot_location_line_and_location_centers__pk');
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
