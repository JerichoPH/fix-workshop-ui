<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('factories', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code",5)->unique()->nullabel(false)->comment("供应商代码");
            $table->string("name", 64)->unique()->nullabel(false)->comment("供应商名称");
            $table->string("short_name",64)->comment("供应商简称");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('factories');
    }
}
