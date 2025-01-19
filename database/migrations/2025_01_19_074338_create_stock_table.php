<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lokasi', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('product_storage', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('id_lokasi')->constrained('lokasi');
            $table->decimal('kapasitas', 30, 2);
            $table->enum('jenis', ['tanki', 'warehouse']);
            $table->timestamps();
        });

        Schema::create('stok_cpo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tanki_id')->constrained('product_storage');
            $table->date('tanggal');
            $table->decimal('qty', 30, 2);
            $table->decimal('umur', 30, 2);
            $table->string('remarks');
            $table->timestamps();
        });

        Schema::create('stok_bulk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_bulky')->constrained('product');
            $table->foreignId('tanki_id')->constrained('product_storage');
            $table->date('tanggal');
            $table->decimal('qty', 30, 2);
            $table->decimal('umur', 30, 2);
            $table->string('remarks');
            $table->timestamps();
        });

        Schema::create('stok_ritel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ritel')->constrained('product');
            $table->foreignId('warehouse_id')->constrained('product_storage');
            $table->date('tanggal');
            $table->decimal('qty', 30, 2);
            $table->decimal('umur', 30, 2);
            $table->string('remarks');
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
        Schema::dropIfExists('stok_ritel');
        Schema::dropIfExists('stok_bulk');
        Schema::dropIfExists('stok_cpo');
        Schema::dropIfExists('product_storage');
        Schema::dropIfExists('lokasi');
    }
}
