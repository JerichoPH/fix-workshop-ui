<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRbacPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rbac_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('name', 64)->nullable(false)->comment('权限名称');
            $table->string('uri', 128)->nullable(false)->comment('权限路由');
            $table->index('uri');
            $table->string('method', 64)->nullable(false)->comment('请求方法');
            $table->index('method');
            $table->string('rbac_permission_group_uuid',36)->nullable(false)->comment('所属权限分组UUID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rbac_permissions');
    }
}
