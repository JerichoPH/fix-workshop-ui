<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKindCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kind_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 3)->nullable(false)->unique()->comment('种类代码（3位：Q01）');
            $table->string('name', 64)->nullable(false)->unique()->comment('种类名称');
            $table->string('nickname', 64)->unique()->comment('昵称');
            $table->boolean('be_enable')->nullable(false)->default(true)->comment('是否可用');
            $table->string('race', 64)->nullable(false)->default('Q')->comment('设备、器材分类：S设备、Q器材');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kind_categories');
    }
}
