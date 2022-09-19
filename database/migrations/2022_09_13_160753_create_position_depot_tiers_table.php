<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePositionDepotTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('position_depot_tiers', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 12)->nullable(false)->comment('仓库柜架层代码（12位）');
            $table->index('unique_code');
            $table->string('name', 64)->nullable(false)->comment('仓库柜架层名称');
            $table->string('position_depot_cabinet_uuid',36)->nullable(false)->comment('所属仓库柜架UUID');
            $table->index('position_depot_cabinet_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('position_depot_tiers');
    }
}
