<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePositionIndoorRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('position_indoor_rooms', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 11)->nullable(false)->comment('室内上道位置机房代码（11位）');
            $table->index('unique_code');
            $table->string('name', 64)->nullable(false)->comment('室内上道位置机房名称');
            $table->string('position_indoor_room_type_uuid', 36)->nullable(false)->comment('室内上道位置机房类型');
            $table->index('position_indoor_room_type_uuid');
            $table->string('location_railroad_uuid', 36)->nullable(false)->default('')->comment('所属道口UUID');
            $table->index('location_railroad_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('position_indoor_rooms');
    }
}
