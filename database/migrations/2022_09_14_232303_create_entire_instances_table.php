<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntireInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entire_instances', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('identity_code', 20)->nullable(false)->unique()->comment('唯一编号');
            $table->string('serial_number', 64)->nullable(true)->comment('所编号');
            $table->index('serial_number');
            $table->string('entire_instance_status_unique_code')->nullable(false)->comment('器材状态代码');
            $table->index('entire_instance_status_unique_code');
            $table->string('kind_category_uuid',36)->nullable(false)->comment('所属种类UUID');
            $table->index('kind_category_uuid');
            $table->string('kind_entire_type_uuid',36)->nullable(false)->comment('所属类型UUID');
            $table->index('kind_entire_type_uuid');
            $table->string('kind_sub_type_uuid',36)->nullable(false)->comment('所属型号UUID');
            $table->index('kind_sub_type_uuid');
            $table->string('factory_uuid',36)->nullable(false)->comment('所属供应商UUID');
            $table->string('factory_made_serial_number',64)->nullable(true)->comment('出厂编号');
            $table->date('factory_made_at')->nullable(true)->comment('出厂日期');
            $table->string('asset_code',64)->comment('物资编号');
            $table->string('fixed_asset_code',64)->comment('固资编号');
            $table->string('parent_identity_code',20)->comment('所属整件编号');
            $table->index('parent_identity_code',20);
            $table->boolean('be_part')->nullable(false)->default(false)->comment('是否是部件');
            $table->text('note')->nullable(true)->comment('备注');
            $table->string('source_name_uuid',64)->comment('所属来源类型UUID');
            $table->index('source_name_uuid',64);
            $table->string('delete_operator_uuid',64)->comment('删除操作人UUID');
            $table->index('delete_operator_uuid');
            $table->string('wiring_system',64)->comment('线制');
            $table->boolean('has_extrusion_shroud')->nullable(false)->default(false)->comment('防挤脱装置');
            $table->string('said_rod',64)->comment('表示杆特征');
            $table->date('use_expire_at')->comment('到期日期');
            $table->datetime('use_destroy_at')->comment('报废时间');
            $table->date('use_next_cycle_repair_at')->comment('下次周期修日期');
            $table->datetime('use_warehouse_in_at')->comment('入库时间');
            $table->string('use_warehouse_position_depot_cell_uuid',36)->comment('库房位置');
            $table->index('use_warehouse_position_depot_cell_uuid');
            $table->datetime('use_repair_current_fixed_at')->comment('检修时间');
            $table->string('use_repair_current_fixer_name',64)->comment('检修人');
            $table->datetime('use_repair_current_checked_at')->comment('验收时间');
            $table->string('use_repair_current_checker_name',64)->comment('验收人');
            $table->datetime('use_repair_current_spot_checked_at')->nullable(true)->comment('抽验时间');
            $table->string('use_repair_current_spot_checker_name',64)->nullable(true)->comment('抽验人');
            $table->datetime('use_repair_last_fixed_at')->nullable(true)->comment('上次检修时间');
            $table->string('use_repair_last_fixer_name',64)->nullable(true)->comment('上次检修人');
            $table->datetime('use_repair_last_checked_at')->nullable(true)->comment('上次验收时间');
            $table->string('use_repair_last_checker_name',64)->nullable(true)->comment('上次验收人');
            $table->datetime('use_repair_last_spot_checked_at')->nullable(true)->comment('上次抽验时间');
            $table->string('use_repair_last_spot_checker_name',64)->nullable(true)->comment('上次抽验人');
            $table->string('belong_to_organization_railway_uuid',64)->nullable(true)->comment('所属路局UUID');
            $table->index('belong_to_organization_railway_uuid');
            $table->string('belong_to_organization_paragraph_uuid',64)->nullable(true)->comment('所属站段UUID');
            $table->index('belong_to_organization_paragraph_uuid');
            $table->string('belong_to_organization_workshop_uuid',64)->nullable(true)->comment('所属车间UUID');
            $table->index('belong_to_organization_workshop_uuid');
            $table->string('belong_to_organization_work_area_uuid',64)->nullable(true)->comment('所属工区UUID');
            $table->index('belong_to_organization_work_area_uuid');
            $table->string('use_place_current_organization_workshop_uuid',64)->nullable(true)->comment('上道地点：车间UUID');
            $table->index('use_place_current_organization_workshop_uuid','entire_instances__upcowu');
            $table->string('use_place_current_organization_work_area_uuid',64)->nullable(true)->comment('上道地点：工区UUID');
            $table->index('use_place_current_organization_work_area_uuid','entire_instances__upcowau');
            $table->string('use_place_current_location_line_uuid',64)->nullable(true)->comment('上道地点：线别UUID');
            $table->index('use_place_current_location_line_uuid','entire_instances__upcllu');
            $table->string('use_place_current_location_station_uuid',64)->nullable(true)->comment('上道地点：站场UUID');
            $table->index('use_place_current_location_station_uuid','entire_instances__upclsu');
            $table->string('use_place_current_location_section_uuid',64)->nullable(true)->comment('上道地点：工区UUID');
            $table->index('use_place_current_location_section_uuid','entire_instances__upclsu2');
            $table->string('use_place_current_location_center_uuid',64)->nullable(true)->comment('上道地点：中心UUID');
            $table->index('use_place_current_location_center_uuid','entire_instances__upclcu');
            $table->string('use_place_current_location_railroad_grade_cross_uuid',64)->nullable(true)->comment('上道地点：道口UUID');
            $table->index('use_place_current_location_railroad_grade_cross_uuid','entire_instances__upclrgcu');
            $table->string('use_place_last_organization_workshop_uuid',64)->nullable(true)->comment('上次上道地点：车间UUID');
            $table->index('use_place_last_organization_workshop_uuid','entire_instances__uplowu');
            $table->string('use_place_last_organization_work_area_uuid',64)->nullable(true)->comment('上次上道地点：工区UUID');
            $table->index('use_place_last_organization_work_area_uuid','entire_instances__uplowau');
            $table->string('use_place_last_location_line_uuid',64)->nullable(true)->comment('上次上道地点：线别UUID');
            $table->index('use_place_last_location_line_uuid','entire_instances__uplllu');
            $table->string('use_place_last_location_station_uuid',64)->nullable(true)->comment('上次上道地点：站场UUID');
            $table->index('use_place_last_location_station_uuid','entire_instances__upllsu');
            $table->string('use_place_last_location_section_uuid',64)->nullable(true)->comment('上次上道地点：工区UUID');
            $table->index('use_place_last_location_section_uuid','entire_instances__upllsu2');
            $table->string('use_place_last_location_center_uuid',64)->nullable(true)->comment('上次上道地点：中心UUID');
            $table->index('use_place_last_location_center_uuid','entire_instances__upllcu');
            $table->string('use_place_last_location_railroad_grade_cross_uuid',64)->nullable(true)->comment('上次上道地点：道口UUID');
            $table->index('use_place_last_location_railroad_grade_cross_uuid','entire_instances__upllrgcu');
            $table->string('use_place_current_position_indoor_cell_uuid',64)->nullable(true)->comment('上道位置：室内上道位置UUID');
            $table->index('use_place_current_position_indoor_cell_uuid','entire_instances__upcpicu');
            $table->string('use_place_last_position_indoor_cell_uuid',64)->nullable(true)->comment('上次上道位置：室内上道位置UUID');
            $table->index('use_place_last_position_indoor_cell_uuid','entire_instances__uplpicu');
            $table->unsignedSmallInteger('ex_cycle_repair_year')->comment('周期修（年）');
            $table->unsignedSmallInteger('ex_life_year')->comment('寿命（年）');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('entire_instances');
    }
}
