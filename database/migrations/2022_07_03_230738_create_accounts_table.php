<?php

use Illuminate\Database\Eloquent\SoftDeletes;
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
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("uuid", 32)->unique()->nullable(false)->comment("uuid");
            $table->string("username", 64)->unique()->nullable(false)->comment("账号");
            $table->string("password", 128)->nullable(false)->comment("密码");
            $table->string("nickname", 64)->unique()->nullable(false)->comment("昵称");
            $table->string("account_status_unique_code", 64)->nullable(false)->comment("用户状态");
            $table->foreign("account_status_unique_code")->references("unique_code")->on("account_statuses")->onUpdate("cascade")->comment("用户状态");
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
