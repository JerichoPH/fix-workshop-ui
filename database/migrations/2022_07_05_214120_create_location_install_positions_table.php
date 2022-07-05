<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationInstallPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_install_positions', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code",15)->unique()->nullable(false)->comment("位代码");
            $table->string("name",64)->unique()->nullable(false)->comment("位名称");
            $table->char("location_install_tier_unique_code",13)->nullable(false)->comment("所属层代码");
            $table->foreign("location_install_tier_unique_code")->references("unique_code")->on("location_install_tiers")->onUpdate("cascade")->comment("所属层");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_install_positions');
    }
}
