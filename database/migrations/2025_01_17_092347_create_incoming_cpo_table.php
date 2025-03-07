<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomingCpoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('source_incoming_cpo', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('target_incoming_cpo', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->decimal('qty', 30, 2);
            $table->timestamps();
        });

        Schema::create('incoming_cpo', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->decimal('qty', 30, 2);
            $table->decimal('harga', 30, 2);
            $table->foreignId('source_id')->constrained('source_incoming_cpo');
            $table->string('remark');
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
        Schema::dropIfExists('source_incoming_cpo');
        Schema::dropIfExists('target_incoming_cpo');
        Schema::dropIfExists('incoming_cpo');
    }
}
