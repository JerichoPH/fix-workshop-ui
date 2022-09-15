<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntireInstanceLogTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entire_instance_log_types', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code',64)->nullable(false)->unique()->comment('器材日志类型代码');
            $table->string('name',64)->nullable(false)->unique()->comment('器材日志类型名称');
            $table->string('unique_code_for_paragraph',64)->nullable(false)->comment('器材日志对应段中心代码');
            $table->string('icon',64)->nullable(false)->comment('器材日志类型图标');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('entire_instance_log_types');
    }
}
