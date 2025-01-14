<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJenisLaporanProduksiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jenis_laporan_produksi', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the report (e.g., Refinery, Frak IV56)
            $table->enum('condition_olah', ['sum', 'use_higher', 'use_lower', 'difference']); // Condition for bahan olah (e.g., sum, use higher, difference)
            $table->timestamps();
        });

        Schema::create('item_produksi', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('jenis_laporan_id')->constrained('jenis_laporan_produksi');
            $table->enum('kategori', ['bahan_olah', 'produk_hasil', 'others']);
            $table->timestamps();
        });

        Schema::create('laporan_produksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_produksi_id')->constrained('item_produksi');
            $table->date('tanggal');
            $table->foreignId('pmg_id')->constrained('pmg');
            $table->decimal('qty', 30, 2);
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
        Schema::dropIfExists('laporan_produksi');
        Schema::dropIfExists('item_produksi');
        Schema::dropIfExists('jenis_laporan_produksi');
    }
}
