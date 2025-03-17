<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCatatanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('catatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_simulation')->constrained('simulation');
            $table->string('judul');
            $table->timestamps();
        });

        Schema::create('detail_catatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_catatan')->constrained('catatan');
            $table->string('teks');
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
        Schema::dropIfExists('detail_catatan');
        Schema::dropIfExists('catatan');
    }
}
