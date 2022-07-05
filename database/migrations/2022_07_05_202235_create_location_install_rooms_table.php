<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationInstallRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_install_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger("id",true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code",7)->unique()->nullable(false)->comment("机房代码");
            $table->string("name",64)->unique()->nullable(false)->comment("机房名称");
            $table->char("organization_station_unique_code",6)->nullable(false)->comment("所属车站代码");
            $table->foreign("organization_station_unique_code","lInstallRooms__osuc")->references("unique_code")->on("organization_stations")->onUpdate("cascade")->comment("所属车站");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_install_rooms');
    }
}
