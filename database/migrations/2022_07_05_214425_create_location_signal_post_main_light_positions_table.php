<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationSignalPostMainLightPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_signal_post_main_light_positions', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 2)->unique("uiLSPMLP__uniqueCode")->nullable(false)->comment("信号机主体灯位代码");
            $table->string("name", 64)->unique("uiLSPMLP__name")->nullable(false)->comment("信号机主体灯位名称");
            $table->char("location_signal_post_main_or_indicator_unique_code", 6)->nullable(false)->comment("所属信号机主体或表示器代码");
            $table->foreign("location_signal_post_main_or_indicator_unique_code", "fLSPMLP__lspmoiuc")->references("unique_code")->on("location_signal_post_main_or_indicators")->onUpdate("cascade")->comment("所属信号机主体或表示器");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_signal_post_main_light_positions');
    }
}
