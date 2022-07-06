<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationInstallTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_install_tiers', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code",13)->unique("uiLIT__uniqueCode")->nullable(false)->comment("层代码");
            $table->string("name",64)->unique("uiLIT__name")->nullable(false)->comment("层名称");
            $table->char("location_install_shelf_unique_code",11)->nullable(false)->comment("所属柜架代码");
            $table->foreign("location_install_shelf_unique_code","fLIT__lisuc")->references("unique_code")->on("location_install_shelves")->comment("所属柜架");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_install_tiers');
    }
}
