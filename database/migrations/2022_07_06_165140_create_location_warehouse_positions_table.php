<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationWarehousePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_warehouse_positions', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 16)->unique("uiLWPositions__uniqueCode")->nullable(false)->comment("仓库位代码");
            $table->string("name", 64)->unique("uiLWPositions__name")->nullable(false)->comment("仓库位名称");
            $table->char("location_warehouse_tier_unique_code", 14)->nullable(false)->comment("所属仓库层代码");
            $table->foreign("location_warehouse_tier_unique_code", "fLWPositions__lwtuc")->references("unique_code")->on("location_warehouse_tiers")->onUpdate("cascade")->comment("所属仓库层");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_warehouse_positions');
    }
}
