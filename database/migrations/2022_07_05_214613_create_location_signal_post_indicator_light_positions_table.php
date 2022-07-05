<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationSignalPostIndicatorLightPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_signal_post_indicator_light_positions', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code",2)->unique()->nullable(false)->comment("信号机表示器灯位代码");
            $table->string("name",64)->unique()->nullable(false)->comment("信号机表示器灯位名称");
            $table->char("lspmoiuc",6)->nullable(false)->comment("所属信号机主体或表示器代码");
            $table->foreign("lspmoiuc","lSignalPostIndicatorLightPositions__lspmoiuc")->references("unique_code")->on("location_signal_post_main_or_indicators")->comment("所属信号机主体或表示器");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_signal_post_indicator_light_positions');
    }
}
