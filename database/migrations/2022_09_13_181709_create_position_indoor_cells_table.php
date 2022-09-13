<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePositionIndoorCellsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('position_indoor_cells', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 15)->nullable(false)->unique()->comment('室内上道位置柜架格位代码（15位）');
            $table->string('name', 64)->nullable(false)->unique()->comment('室内上道位置柜架格位名称');
            $table->string('position_indoor_row_uuid', 36)->nullable(false)->comment('所属室内上道位置柜架层UUID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('position_indoor_cells');
    }
}
