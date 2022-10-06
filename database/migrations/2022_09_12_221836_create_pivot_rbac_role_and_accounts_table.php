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
            $table->string('rbac_role_uuid')->nullabel(false)->comment('所属角色UUID');
            $table->string('account_uuid')->nullable(false)->comment('所属用户UUID');
            $table->primary(['rbac_role_uuid', 'account_uuid']);
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
