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

            $table->string('username', 64)->nullable(false)->unique()->comment('用户名');
            $table->string('password', 128)->nullable(false)->comment('密码');
            $table->string('nickname', 64)->nullable(false)->unique()->comment('昵称');

            $table->string('organization_railway_uuid')->comment('归属路局UUID');
            $table->string('organization_paragraph_uuid')->comment('归属站段UUID');
            $table->string('organization_workshop_uuid')->comment('归属车间UUID');
            $table->string('organization_work_area_uuid')->comment('归属工区UUID');
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
