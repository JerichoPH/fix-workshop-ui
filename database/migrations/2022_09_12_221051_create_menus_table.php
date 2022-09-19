<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('name', 64)->nullable(false)->comment('菜单名称');
            $table->string('url', 128)->nullable(true)->comment('菜单URL');
            $table->string('uri_name', 64)->nullable(true)->comment('菜单路由标识');
            $table->string('icon', 64)->nullable(true)->comment('菜单图标');
            $table->string('parent_uuid', 36)->nullable(true)->comment('父级UUID');
            $table->index('parent_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
    }
}
