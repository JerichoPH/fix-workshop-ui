<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKindSubTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kind_sub_types', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->timestamps();
            $table->softDeletes();
            $table->char("unique_code", 7)->unique("uiKST__uniqueCode")->nullable(false)->comment("种类代码");
            $table->string("name", 64)->unique("uiKST__name")->nullable(false)->comment("种类代码");
            $table->string("nickname", 64)->comment("打印别名");
            $table->boolean("be_enable")->nullable(false)->default(true)->comment("是否启用");
            $table->char("kind_entire_type_unique_code", 5)->nullable(false)->default(true)->comment("所属类型代码");
            $table->foreign("kind_entire_type_unique_code","uiKST__kindEntireTypeUniqueCode")->references("unique_code")->on("kind_entire_types")->onUpdate("cascade")->comment("所属类型");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kind_sub_types');
    }
}
