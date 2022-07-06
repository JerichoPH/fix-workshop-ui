<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationWarehouseTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_warehouse_tiers', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 16)->unique("uiLWT__uniqueCode")->nullable(false)->comment("仓库层代码");
            $table->string("name", 64)->unique("uiLWT__name")->nullable(false)->comment("仓库层名称");
            $table->char("location_warehouse_shelf_unique_code", 14)->nullable(false)->comment("所属仓库柜架代码");
            $table->foreign("location_warehouse_shelf_unique_code", "fLWT__lwsuc")->references("unique_code")->on("location_warehouse_shelves")->onUpdate("cascade")->comment("所属仓库柜架");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_warehouse_tiers');
    }
}
