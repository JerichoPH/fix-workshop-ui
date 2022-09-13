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
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 3)->nullable(false)->unique()->comment('路局代码（3位：A12）');
            $table->string('name', 64)->nullable(false)->unique()->comment('路局名称');
            $table->string('short_name', 64)->nullable(false)->unique()->comment('路局简称');
            $table->boolean('be_enable')->nullable(false)->default(true)->comment('是否可用');
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