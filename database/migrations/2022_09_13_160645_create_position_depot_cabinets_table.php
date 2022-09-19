<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePositionDepotCabinetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('position_depot_cabinets', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 10)->nullable(false)->comment('仓库柜架代码（10位）');
            $table->index('unique_code');
            $table->string('name', 64)->nullable(false)->comment('仓库柜架名称');
            $table->string('position_depot_row_uuid',36)->nullable(false)->comment('所属仓库排UUID');
            $table->index('position_depot_row_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('position_depot_cabinets');
    }
}
