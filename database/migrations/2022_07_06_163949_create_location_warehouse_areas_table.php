<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationWarehouseAreasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_warehouse_areas', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 10)->unique("uiLWA__uniqueCode")->nullable(false)->comment("仓库分区代码");
            $table->string("name", 64)->unique("uiLWA__name")->nullable(false)->comment("仓库分区名称");
            $table->char("location_warehouse_storehouse_unique_code",8)->nullable(false)->comment("所属仓库代码");
            $table->foreign("location_warehouse_storehouse_unique_code","fLWA__lwsuc")->references("unique_code")->on("location_warehouse_storehouses")->onUpdate("cascade")->comment("所属仓库");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_warehouse_areas');
    }
}
