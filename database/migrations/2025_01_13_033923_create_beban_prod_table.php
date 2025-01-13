<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBebanProdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('beban_prod', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uraian_id')->constrained('uraian_cost_prod');
            $table->foreignId('plant_id')->constrained('plant');
            $table->date('tanggal');
            $table->decimal('value', 30, 2);

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
        Schema::dropIfExists('beban_prod');
    }
}
