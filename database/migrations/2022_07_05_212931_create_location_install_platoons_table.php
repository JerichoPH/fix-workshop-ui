<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationInstallPlatoonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_install_platoons', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code",9)->unique()->nullable(false)->comment("排代码");
            $table->string("name",64)->unique()->nullable(false)->comment("排名称");
            $table->char("location_install_room_unique_code",7)->nullable(false)->comment("所属机房代码");
            $table->foreign("location_install_room_unique_code","lInstallPlatoons_liruc")->references("unique_code")->on("location_install_rooms")->comment("所属机房");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_install_platoons');
    }
}
