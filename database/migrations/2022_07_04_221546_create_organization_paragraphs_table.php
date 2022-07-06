<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationParagraphsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organization_paragraphs', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 4)->unique("uiOP__uniqueCode")->nullable(false)->comment("站段代码");
            $table->string("name", 64)->unique("uiOP__name")->nullable(false)->comment("站段名称");
            $table->string("short_name", 64)->comment("站段简称");
            $table->boolean("be_enable")->nullable(false)->default(true)->comment("是否启用");
            $table->string("organization_railway_unique_code")->nullable(false)->comment("所属路局名称");
            $table->foreign("organization_railway_unique_code","fOP__oruc")->references("unique_code")->on("organization_railways")->onUpdate("cascade")->comment("所属路局");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organization_paragraphs');
    }
}
