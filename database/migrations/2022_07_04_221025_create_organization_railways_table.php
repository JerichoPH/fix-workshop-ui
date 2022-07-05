<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationRailwaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organization_railways', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 3)->unique()->nullable(false)->comment("路局代码");
            $table->string("name", 64)->unique()->nullable(false)->comment("路局名称");
            $table->string("short_name", 64)->comment("路局简称");
            $table->boolean("be_enable")->nullable(false)->default(true)->comment("是否启用");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organization_railways');
    }
}
