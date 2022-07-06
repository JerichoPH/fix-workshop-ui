<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationWorkAreasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organization_work_areas', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 8)->unique("uiOWA__uniqueCode")->nullable(false)->comment("工区代码");
            $table->string("name", 64)->unique("uiOWA__name")->nullable(false)->comment("工区名称");
            $table->boolean("be_enable")->nullable(false)->default(true)->comment("是否启用");
            $table->string("organization_work_area_type_unique_code", 64)->nullable(false)->comment("所属工区类型代码");
            $table->foreign("organization_work_area_type_unique_code","fOWA__owatuc")->references("unique_code")->on("organization_work_area_types")->onUpdate("cascade")->comment("所属工区类型");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organization_work_areas');
    }
}
