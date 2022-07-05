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
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 7)->unique()->nullable(false)->comment("车间代码");
            $table->string("name", 64)->unique()->nullable(false)->comment("车间名称");
            $table->boolean("be_enable")->nullable(false)->default(true)->comment("是否启用");
            $table->string("organization_workshop_type_unique_code", 64)->nullable(false)->comment("所属车间类型代码");
            $table->foreign("organization_workshop_type_unique_code")->references("unique_code")->on("organization_workshop_unique_code")->onUpdate("cascade")->comment("所属车间类型");
            $table->char("organization_paragraph_unique_code", 4)->nullable(false)->comment("所属站段代码");
            $table->foreign("organization_paragraph_unique_code")->references("unique_code")->on("organization_paragraphs")->onUpdate("cascade")->comment("所属站段");
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
