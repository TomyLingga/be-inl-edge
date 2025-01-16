<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashFlowScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kategori_cash_flow_schedule', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('pay_status_cash_flow_schedule', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('boolean');
            $table->string('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_flow_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori_cash_flow_schedule');
            $table->foreignId('pmg_id')->constrained('pmg');
            $table->string('name');
            $table->date('tanggal');
            $table->decimal('value', 30, 2);
            $table->foreignId('pay_status_id')->constrained('pay_status_cash_flow_schedule');
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
        Schema::dropIfExists('kategori_cash_flow_schedule');
        Schema::dropIfExists('pay_status_cash_flow_schedule');
        Schema::dropIfExists('cash_flow_schedule');
    }
}
