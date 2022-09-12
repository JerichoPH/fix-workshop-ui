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
            $table->integer('rbac_role_id')->nullabel(false)->comment('所属角色ID');
            $table->integer('menu_id')->nullable(false)->comment('所属菜单ID');
            $table->primary(['rbac_role_id', 'menu_id']);
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
