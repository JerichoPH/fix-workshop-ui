<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntireInstanceStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entire_instance_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 64)->nullable(false)->unique()->comment('器材状态代码');
            $table->string('name', 64)->nullable(false)->unique()->comment('器材状态名称');
            $table->string('number_code',2)->nullable(false)->unique()->comment('器材状态数字代码');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('entire_instance_statuses');
    }
}
