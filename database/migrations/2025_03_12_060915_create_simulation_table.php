<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSimulationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simulation', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id')->nullable();
            $table->string('name');
            $table->date('date');
            $table->decimal('kurs', 30, 2);
            $table->decimal('expected_margin', 30, 2);
            $table->foreignId('id_dmo')->constrained('dmo');
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
        Schema::dropIfExists('simulation');
    }
}
