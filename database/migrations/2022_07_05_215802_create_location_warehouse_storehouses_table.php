<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationWarehouseStorehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_warehouse_storehouses', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 8)->unique()->nullable(false)->comment("仓库代码");
            $table->string("name", 64)->unique()->nullable(false)->comment("仓库名称");
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
        Schema::dropIfExists('location_warehouse_storehouses');
    }
}
