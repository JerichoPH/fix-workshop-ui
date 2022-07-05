<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationSectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organization_sections', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 6)->unique()->nullable(false)->comment("区间代码");
            $table->string("name", 64)->unique()->nullable(false)->comment("区间名称");
            $table->boolean("be_enable")->nullable(false)->default(true)->comment("是否启用");
            $table->char("organization_workshop_unique_code", 7)->nullable(false)->comment("所属车间代码");
            $table->foreign("organization_workshop_unique_code")->references("unique_code")->on("organization_workshops")->comment("所属车间");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organization_sections');
    }
}
