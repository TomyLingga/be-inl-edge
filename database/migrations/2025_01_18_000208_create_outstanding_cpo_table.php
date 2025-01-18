<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutstandingCpoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('kontak');
            $table->string('negara');
            $table->string('provinsi');
            $table->string('kota');
            $table->string('alamat');
            $table->timestamps();
        });

        Schema::create('outstanding_cpo', function (Blueprint $table) {
            $table->id();
            $table->string('kontrak');
            $table->foreignId('supplier_id')->constrained('supplier');
            $table->decimal('qty', 30, 2);
            $table->decimal('harga', 30, 2);
            $table->boolean('status');
            $table->timestamps();
        });

        Schema::create('saldo_pe', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->decimal('saldo_awal', 30, 2);
            $table->decimal('saldo_pakai', 30, 2);
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
        Schema::dropIfExists('saldo_pe');
        Schema::dropIfExists('outstanding_cpo');
        Schema::dropIfExists('supplier');
    }
}
