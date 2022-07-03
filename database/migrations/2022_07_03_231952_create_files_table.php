<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->timestamps();
            $table->softDeletes();
            $table->string("uuid",32)->unique()->nullable(false)->comment("uuid");
            $table->string("filename",128)->nullable(false)->comment("文件存储名");
            $table->string("type",128)->nullable(false)->comment("文件类型");
            $table->string("original_filename",128)->nullable(false)->comment("原始文件名");
            $table->string("original_extension",32)->nullable(false)->comment("原始文件扩展名");
            $table->string("size",128)->nullable(false)->comment("文件大小");
            $table->char("upload_operator_uuid",32)->nullable(false)->comment("上传人编号");
            $table->foreign("upload_operator_uuid")->references("uuid")->on("accounts")->comment("上传人");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
