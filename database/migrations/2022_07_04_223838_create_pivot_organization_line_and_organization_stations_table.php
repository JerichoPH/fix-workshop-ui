<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePivotOrganizationLineAndOrganizationStationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pivot_organization_line_and_organization_stations', function (Blueprint $table) {
            $table->unsignedBigInteger("id", true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger("organization_line_id")->nullable(false)->comment("所属线别ID");
            $table->foreign("organization_line_id","polaos__oli")->references("id")->on("organization_lines")->onUpdate("cascade")->comment("所属线别");
            $table->unsignedBigInteger("organization_station_id")->nullable(false)->comment("所属站场ID");
            $table->foreign("organization_station_id","polaos__osi")->references("id")->on("organization_stations")->onUpdate("cascade")->comment("所属站场");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pivot_organization_line_and_organization_stations');
    }
}
