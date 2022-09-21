<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationStationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_stations', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 6)->nullable(false)->comment('站场代码（6位：G00001）');
            $table->index('unique_code');
            $table->string('name', 64)->nullable(false)->comment('站场名称');
            $table->boolean('be_enable')->nullable(false)->default(true)->comment('是否可用');
            $table->string('organization_workshop_uuid', 36)->nullable(false)->comment('所属车间UUID');
            $table->index('organization_workshop_uuid');
            $table->string('organization_work_area_uuid', 36)->nullable(false)->default('')->comment('所属工区UUID');
            $table->index('organization_work_area_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_stations');
    }
}
