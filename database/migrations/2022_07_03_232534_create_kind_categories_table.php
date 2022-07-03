<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKindCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kind_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 3)->unique()->nullable(false)->comment("种类代码");
            $table->string("name", 64)->unique()->nullable(false)->comment("种类代码");
            $table->string("nickname", 64)->comment("打印别名");
            $table->boolean("be_active")->nullable(false)->default(true)->comment("是否启用");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kind_categories');
    }
}
