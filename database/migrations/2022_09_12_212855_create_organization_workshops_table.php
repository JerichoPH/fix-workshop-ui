<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationWorkshopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organization_workshops', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('unique_code', 7)->nullable(false)->comment('车间代码（7位：B048C01）');
            $table->index('unique_code');
            $table->string('name', 64)->nullable(false)->comment('车间名称');
            $table->boolean('be_enable')->nullable(false)->default(true)->comment('是否可用');
            $table->string('organization_workshop_type_uuid', 36)->nullable(false)->comment('所属车间类型UUID');
            $table->index('organization_workshop_type_uuid');
            $table->string('organization_paragraph_uuid', 36)->nullable(false)->comment('所属站段UUID');
            $table->index('organization_paragraph_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organization_workshops');
    }
}
