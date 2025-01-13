<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTargetProduksiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('target_produksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uraian_id')->constrained('target_produksi_uraian');
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
        Schema::dropIfExists('target_produksi');
    }
}
