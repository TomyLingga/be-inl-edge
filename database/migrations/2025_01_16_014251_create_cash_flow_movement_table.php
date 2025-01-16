<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashFlowMovementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kategori_cash_flow_movement', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('nilai', ['positive', 'negative']);
            $table->timestamps();
        });

        Schema::create('cash_flow_movement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori_cash_flow_movement');
            $table->foreignId('pmg_id')->constrained('pmg');
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
        Schema::dropIfExists('kategori_cash_flow_movement');
        Schema::dropIfExists('cash_flow_movement');
    }
}
