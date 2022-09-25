<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('username', 64)->nullable(false)->comment('用户名');
            $table->index('username');
            $table->string('password', 128)->nullable(false)->comment('密码');
            $table->string('nickname', 64)->nullable(false)->unique()->comment('昵称');
            $table->boolean('be_super_admin')->nullable(false)->default(0)->comment('超级管理员');

            $table->string('organization_railway_uuid')->nullable(false)->default('')->comment('归属路局UUID');
            $table->index('organization_railway_uuid');
            $table->string('organization_paragraph_uuid')->nullable(false)->default('')->comment('归属站段UUID');
            $table->index('organization_paragraph_uuid');
            $table->string('organization_workshop_uuid')->nullable(false)->default('')->comment('归属车间UUID');
            $table->index('organization_workshop_uuid');
            $table->string('organization_work_area_uuid')->nullable(false)->default('')->comment('归属工区UUID');
            $table->index('organization_work_area_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
    }
}
