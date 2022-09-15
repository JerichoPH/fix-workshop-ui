<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntireInstanceLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entire_instance_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('name', 64)->nullable(false)->comment('器材日志名称');
            $table->text('url')->comment('网址');
            $table->string('operator_uuid',36)->nullable(false)->comment('所属操作人UUID');
            $table->index('operator_uuid');
            $table->string('entire_instance_identity_code',20)->nullable(false)->comment('所属器材唯一编号');
            $table->index('entire_instance_identity_code');
            $table->string('organization_railway_uuid',36)->nullable(false)->comment('所属路局');
            $table->index('organization_railway_uuid');
            $table->string('organization_paragraph_uuid',36)->comment('所属站段UUID');
            $table->index('organization_paragraph_uuid');
            $table->string('organization_workshop_uuid',36)->comment('所属车间UUID');
            $table->index('organization_workshop_uuid');
            $table->string('organization_work_area_uuid',36)->comment('所属工区UUID');
            $table->index('organization_work_area_uuid');
            $table->string('location_line_uuid',36)->comment('所属线别UUID');
            $table->index('location_line_uuid');
            $table->string('location_station_uuid',36)->comment('所属线别UUID');
            $table->index('location_station_uuid');
            $table->string('location_section_uuid',36)->comment('所属区间UUID');
            $table->index('location_section_uuid');
            $table->string('location_center_uuid',36)->comment('所属中心UUID');
            $table->index('location_center_uuid');
            $table->string('location_railroad_grade_cross_uuid',36)->comment('所属道口UUID');
            $table->index('location_railroad_grade_cross_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('entire_instance_logs');
    }
}
