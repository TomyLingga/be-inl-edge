<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSdmTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('uraian_sdm', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the report (e.g., Chemical Consumption, Steam Consumption)
            $table->timestamps();
        });

        Schema::create('sdm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uraian_id')->constrained('uraian_sdm');
            $table->date('tanggal');
            $table->string('nilai');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sdm');
        Schema::dropIfExists('uraian_sdm');
    }
}
