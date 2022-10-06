<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePivotLocationLineAndLocationRailroadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pivot_location_line_and_location_railroads', function (Blueprint $table) {
            $table->string('location_line_uuid', 36);
            $table->string('location_railroad_uuid', 36);
            $table->primary(['location_line_uuid', 'location_railroad_uuid',], 'pivot_location_line_and_location_railroad__pk');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pivot_location_line_and_location_railroads');
    }
}
