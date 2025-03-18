<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_costs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('contribute_to_margin');
            $table->boolean('contribute_to_proportion');
            $table->boolean('contribute_to_dmo');
            $table->timestamps();
        });

        Schema::create('costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_master_cost')->constrained('master_costs');
            $table->decimal('value', 30, 2);
            $table->foreignId('id_utilisasi')->constrained('utilisasi');
            $table->foreignId('id_simulation')->constrained('simulation');
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
        Schema::dropIfExists('costs');
        Schema::dropIfExists('master_costs');
    }
}
