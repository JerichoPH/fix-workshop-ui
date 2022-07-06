<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationInstallShelvesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_install_shelves', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code",11)->unique("uiLIS__uniqueCode")->nullable(false)->comment("柜架代码");
            $table->string("name",64)->unique("uiLIS__name")->nullable(false)->comment("柜架名称");
            $table->char("location_install_platoon_unique_code",9)->nullable(false)->comment("所属排代码");
            $table->foreign("location_install_platoon_unique_code","fLIS__lipuc")->references("unique_code")->on("location_install_platoons")->onUpdate("cascade")->comment("所属排");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_install_shelves');
    }
}
