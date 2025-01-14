<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJenisLaporanMaterialTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jenis_laporan_material', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the report (e.g., Chemical Consumption, Steam Consumption)
            $table->enum('condition_olah', ['sum', 'use_higher', 'use_lower', 'difference']); // Condition for bahan olah (e.g., sum, use higher, difference)
            $table->timestamps();
        });

        Schema::create('item_material', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('jenis_laporan_id')->constrained('jenis_laporan_material');
            $table->enum('kategori', ['incoming', 'outgoing', 'proportion', 'others']);
            $table->timestamps();
        });

        Schema::create('norma_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_material_id')->constrained('item_material');
            $table->foreignId('pmg_id')->constrained('pmg');
            $table->date('tanggal');
            $table->decimal('qty', 30, 2);
            $table->string('satuan');
            $table->timestamps();
        });

        Schema::create('laporan_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_material_id')->constrained('item_material');
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
        Schema::dropIfExists('laporan_material');
        Schema::dropIfExists('item_material');
        Schema::dropIfExists('norma_material');
        Schema::dropIfExists('jenis_laporan_material');
    }
}
