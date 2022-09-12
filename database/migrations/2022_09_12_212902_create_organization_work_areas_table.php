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
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 3)->nullable(false)->unique()->comment('工区代码');
            $table->string('name', 64)->nullable(false)->unique()->comment('工区名称');
            $table->boolean('be_enable')->nullable(false)->default(true)->comment('是否可用');
            $table->string('organization_work_area_type_uuid', 36)->nullable(false)->comment('所属工区类型UUID');
            $table->index('organization_work_area_type_uuid');
            $table->string('organization_work_area_profession_uuid', 36)->nullabel(false)->comment('所属工区专业UUID');
            $table->index('organization_work_area_profession_uuid');
            $table->string('organization_workshop_uuid', 36)->nullable(false)->comment('所属车间UUID');
            $table->index('organization_workshop_uuid');
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
