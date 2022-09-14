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

            $table->string('identity_code', 20)->nullable(false)->unique()->comment('器材类型代码');
            $table->string('serial_number', 64)->nullable(false)->comment('器材类型代码');
            $table->index('serial_number');
            $table->string('entire_instance_status_unique_code')->nullable(false)->comment('器材状态代码');
            $table->index('entire_instance_status_unique_code');
            $table->string('kind_category_uuid',36)->nullable(false)->comment('所属种类UUID');
            $table->string('kind_entire_type_uuid',36)->nullable(false)->comment('所属类型UUID');
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
