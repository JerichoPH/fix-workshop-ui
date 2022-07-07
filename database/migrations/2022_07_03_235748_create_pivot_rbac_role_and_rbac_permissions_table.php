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
            $table->unsignedBigInteger('id', true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger("rbac_role_id")->nullable(false)->comment("所属权限ID");
            $table->foreign("rbac_role_id", "fPRRRP__rri")->references("id")->on("rbac_roles")->onUpdate("cascade")->comment("所属角色");
            $table->unsignedBigInteger("rbac_permission_id")->nullable(false)->comment("所属权限ID");
            $table->foreign("rbac_permission_id", "fPRRAA__rpi")->references("id")->on("rbac_permissions")->onUpdate("cascade")->comment("所属权限");
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
