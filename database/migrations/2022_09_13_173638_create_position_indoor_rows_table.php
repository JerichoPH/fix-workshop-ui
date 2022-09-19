<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePositionIndoorRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('position_indoor_rows', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 9)->nullable(false)->comment('室内上道位置机房排代码（9位）');
            $table->index('unique_code');
            $table->string('name', 64)->nullable(false)->comment('室内上道位置机房排名称');
            $table->string('position_indoor_room_uuid', 36)->nullable(false)->comment('所属室内上道位置机房UUID');
            $table->index('position_indoor_room_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('position_indoor_rows');
    }
}
