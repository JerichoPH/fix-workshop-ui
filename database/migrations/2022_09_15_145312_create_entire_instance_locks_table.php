<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntireInstanceLocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entire_instance_locks', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('uuid', 36)->nullable(false)->unique()->comment('uuid');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序');

            $table->string('entire_instance_identity_code', 20)->nullable(false)->comment('所属器材唯一编号');
            $table->index('entire_instance_identity_code');
            $table->datetime('expire_at')->nullable(false)->comment('器材锁过期时间');
            $table->string('lock_name', 64)->nullabel(false)->comment('器材锁名称');
            $table->text('lock_description')->nullable(true)->comment('器材锁描述');
            $table->string('business_order_table_name', 128)->nullable(true)->comment('业务相关单据表名称');
            $table->string('business_order_uuid', 36)->nullable(true)->comment('业务相关表UUID');
            $table->string('business_item_table_name', 128)->nullable(true)->comment('业务相关子项表名称');
            $table->string('business_item_uuid', 36)->nullable(true)->comment('业务相关子项UUID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('entire_instance_locks');
    }
}
