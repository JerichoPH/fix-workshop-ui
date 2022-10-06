<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePivotRbacRoleAndMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pivot_rbac_role_and_menus', function (Blueprint $table) {
            $table->string('rbac_role_uuid',36)->nullabel(false)->comment('所属角色UUID');
            $table->string('menu_uuid',36)->nullable(false)->comment('所属菜单UUID');
            $table->primary(['rbac_role_uuid', 'menu_uuid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pivot_rbac_role_and_menus');
    }
}
