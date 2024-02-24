<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('batch_slot_time', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('batch_slot_id')->constrained('batch_slots');
            $table->foreignUuid('slot_time_id')->constrained('slot_times');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('batch_slot_time');
    }
};
