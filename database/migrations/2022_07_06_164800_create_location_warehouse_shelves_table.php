<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationWarehouseShelvesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_warehouse_shelves', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 14)->unique("uiLWShelves__uniqueCode")->nullable(false)->comment("仓库柜架代码");
            $table->string("name", 64)->unique("uiLWShelves__name")->nullable(false)->comment("仓库柜架名称");
            $table->char("location_warehouse_platoon_unique_code", 12)->nullable(false)->comment("所属仓库排代码");
            $table->foreign("location_warehouse_platoon_unique_code", "fLWShelves__lwpuc")->references("unique_code")->on("location_warehouse_platoons")->onUpdate("cascade")->comment("所属仓库排");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_warehouse_shelves');
    }
}
