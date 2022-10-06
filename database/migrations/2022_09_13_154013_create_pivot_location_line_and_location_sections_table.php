<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePivotLocationLineAndLocationSectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pivot_location_line_and_location_sections', function (Blueprint $table) {
            $table->string('location_line_uuid', 36);
            $table->string('location_section_uuid', 36);
            $table->primary(['location_line_uuid', 'location_section_uuid',], 'pivot_location_line_and_location_sections__pk');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pivot_location_line_and_location_sections');
    }
}
