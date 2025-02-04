<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('target_penjualan_uraian', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('target_penjualan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('product');
            $table->foreignId('uraian_id')->constrained('target_penjualan_uraian');
            $table->decimal('qty', 30, 2);
            $table->date('tanggal');

            $table->timestamps();
        });

        Schema::create('laporan_penjualan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('product');
            $table->string('kontrak');
            $table->decimal('qty', 30, 2);
            $table->decimal('harga_satuan', 30, 2);
            $table->date('tanggal');
            $table->foreignId('customer_id')->constrained('customer');
            $table->decimal('margin_percent', 30, 2);
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
        Schema::dropIfExists('penjualan');
    }
}
