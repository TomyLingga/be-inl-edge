<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLevyReutersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('jenis', ['bulk', 'ritel']);
            $table->decimal('konversi_ton', 30);
            $table->decimal('konversi_pallet', 30);
            $table->decimal('konversi_pouch', 30);
            $table->timestamps();
        });

        Schema::create('levy_duty', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_bulky')->constrained('product');
            $table->date('tanggal');
            $table->decimal('nilai', 30, 2);
            $table->foreignId('id_mata_uang')->constrained('mata_uang');
            $table->timestamps();
        });

        Schema::create('market_routers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_bulky')->constrained('product');
            $table->date('tanggal');
            $table->decimal('nilai', 30, 2);
            $table->foreignId('id_mata_uang')->constrained('mata_uang');
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
        Schema::dropIfExists('product');
    }
}
