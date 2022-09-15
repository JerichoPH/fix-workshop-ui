<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePivotRbacRoleAndRbacPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pivot_rbac_role_and_rbac_permissions', function (Blueprint $table) {
            $table->integer('rbac_role_id');
            $table->integer('rbac_permission_id');
            $table->primary(['rbac_role_id', 'rbac_permission_id'],'pivot_rbac_role_and_rbac_permissions__pk');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pivot_rbac_role_and_rbac_permissions');
    }
}
