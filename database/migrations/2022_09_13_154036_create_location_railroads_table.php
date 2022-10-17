<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationRailroadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_railroads', function (Blueprint $table) {
            $table->integer('id',true)->index('location_railroads__pk');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 5)->nullable(false)->comment('道口代码（5位：I0001）');
            $table->index('unique_code');
            $table->string('name', 64)->nullable(false)->comment('道口名称');
            $table->boolean('be_enable')->nullable(false)->default(true)->comment('是否可用');
            $table->string('organization_workshop_uuid', 36)->nullable(false)->comment('所属车间UUID');
            $table->index('organization_workshop_uuid','location_railroads__owu');
            $table->string('organization_work_area_uuid', 36)->nullable(false)->default('')->comment('所属工区UUID');
            $table->index('organization_work_area_uuid','location_railroads__owua');
	        $table->string('location_line_uuid',36)->nullable(true)->comment('所属线别UUID');
	        $table->index('location_line_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_railroads');
    }
}
