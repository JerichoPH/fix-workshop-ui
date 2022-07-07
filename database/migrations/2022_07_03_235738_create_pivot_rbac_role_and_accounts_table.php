<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePivotRbacRoleAndAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pivot_rbac_role_and_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger("rbac_role_id")->nullable(false)->comment("所属权限ID");
            $table->foreign("rbac_role_id","fPRRAA__rri")->references("id")->on("rbac_roles")->onUpdate("cascade")->comment("所属角色");
            $table->unsignedBigInteger("account_id")->nullable(false)->comment("所属用户ID");
            $table->foreign("rbac_role_id","fPRRAA__ai")->references("id")->on("accounts")->onUpdate("cascade")->comment("所属用户");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pivot_rbac_role_and_accounts');
    }
}
