<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaporanPackagingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packaging', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('target_packaging_uraian', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('jenis_laporan_packaging', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the report (e.g., Refinery, Frak IV56)
            $table->enum('condition_olah', ['sum', 'use_higher', 'use_lower', 'difference']); // Condition for bahan olah (e.g., sum, use higher, difference)
            $table->timestamps();
        });

        Schema::create('target_packaging', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uraian_id')->constrained('target_packaging_uraian');
            $table->foreignId('packaging_id')->constrained('packaging');
            $table->foreignId('jenis_id')->constrained('jenis_laporan_packaging');
            $table->date('tanggal');
            $table->decimal('value', 30, 2);

            $table->timestamps();
        });

        Schema::create('item_packaging', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id')->nullable();
            $table->string('name');
            $table->foreignId('jenis_laporan_id')->constrained('jenis_laporan_packaging');
            $table->enum('kategori', ['bahan_olah', 'produk_hasil', 'others']);
            $table->timestamps();
        });

        Schema::create('laporan_packaging', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_packaging_id')->constrained('item_packaging');
            $table->date('tanggal');
            $table->foreignId('packaging_id')->constrained('packaging');
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
        Schema::dropIfExists('laporan_packaging');
        Schema::dropIfExists('item_packaging');
        Schema::dropIfExists('target_packaging');
        Schema::dropIfExists('jenis_laporan_packaging');
        Schema::dropIfExists('target_packaging_uraian');
        Schema::dropIfExists('packaging');
    }
}
