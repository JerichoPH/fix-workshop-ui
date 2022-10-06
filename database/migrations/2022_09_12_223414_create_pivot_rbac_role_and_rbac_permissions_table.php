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
            $table->string('rbac_role_uuid', 36);
            $table->string('rbac_permission_uuid', 36);
            $table->primary(['rbac_role_uuid', 'rbac_permission_uuid'], 'pivot_rbac_role_and_rbac_permissions__pk');
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
