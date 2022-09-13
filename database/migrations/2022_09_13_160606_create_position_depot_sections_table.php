<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePositionDepotSectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('position_depot_sections', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 6)->nullable(false)->unique()->comment('仓库区域代码（6位）');
            $table->string('name', 64)->nullable(false)->unique()->comment('仓库区域名称');
            $table->string('position_depot_storehouse_uuid',36)->nullable(false)->comment('所属仓库UUID');
            $table->index('position_depot_storehouse_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('position_depot_sections');
    }
}
