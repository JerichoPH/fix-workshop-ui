<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationWarehousePlatoonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_warehouse_platoons', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 12)->unique("uiLWP__uniqueCode")->nullable(false)->comment("仓库排代码");
            $table->string("name", 64)->unique("uiLWP__name")->nullable(false)->comment("仓库排名称");
            $table->char("location_warehouse_area_unique_code", 10)->nullable(false)->comment("所属仓库分区代码");
            $table->foreign("location_warehouse_area_unique_code", "fLWP__lwauc")->references("unique_code")->on("location_warehouse_areas")->onUpdate("cascade")->comment("所属仓库分区");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_warehouse_platoons');
    }
}
