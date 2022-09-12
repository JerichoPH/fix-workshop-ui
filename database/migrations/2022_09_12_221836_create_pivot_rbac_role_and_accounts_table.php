<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->integer('rbac_role_id')->nullabel(false)->comment('所属角色ID');
            $table->integer('account_id')->nullable(false)->comment('所属用户ID');
            $table->primary(['rbac_role_id', 'account_id']);
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
