<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKindSubTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kind_sub_types', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 7)->nullable(false)->comment('型号代码（7位：Q010203');
            $table->index('unique_code');
            $table->string('name', 64)->nullable(false)->comment('型号名称');
            $table->string('nickname', 64)->nullable(true)->comment('昵称');
            $table->string('kind_entire_type_uuid', 36)->nullable(false)->comment('类型种类UUID');
            $table->index('kind_entire_type_uuid');
            $table->smallInteger('cycle_repair_year')->nullable(false)->default(0)->comment('周期修（年）');
            $table->smallInteger('life_year')->nullable(false)->default(15)->comment('寿命（年）');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kind_sub_types');
    }
}
