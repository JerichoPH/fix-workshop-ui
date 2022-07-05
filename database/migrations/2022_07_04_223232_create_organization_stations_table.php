<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationStationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organization_stations', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 6)->unique()->nullable(false)->comment("站场代码");
            $table->string("name", 64)->unique()->nullable(false)->comment("站场名称");
            $table->boolean("be_enable")->nullable(false)->default(true)->comment("是否启用");
            $table->char("organization_workshop_unique_code")->nullable(false)->comment("所属车间代码");
            $table->foreign("organization_workshop_unique_code")->references("unique_code")->on("organization_workshops")->comment("所属车间");
            $table->char("organization_work_area_unique_code")->nullable(true)->comment("所属工区代码");
            $table->foreign("organization_work_area_unique_code")->references("unique_code")->on("organization_work_areas")->comment("所属工区");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organization_stations');
    }
}
