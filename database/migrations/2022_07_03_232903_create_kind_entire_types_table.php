<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKindEntireTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kind_entire_types', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 5)->unique()->nullable(false)->comment("种类代码");
            $table->string("name", 64)->unique()->nullable(false)->comment("种类代码");
            $table->string("nickname", 64)->comment("打印别名");
            $table->boolean("be_enable")->nullable(false)->default(true)->comment("是否启用");
            $table->char("kind_category_unique_code", 3)->nullable(false)->comment("所属种类代码");
            $table->foreign("kind_category_unique_code")->references("unique_code")->on("kind_categories")->comment("所属种类");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kind_entire_types');
    }
}
