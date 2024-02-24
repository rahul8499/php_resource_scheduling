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
        Schema::create('batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_code');
            $table->integer('duration');
            $table->string('duration_type');
            $table->date('starting_date');
            $table->json('selected_days')->nullable();
            // $table->integer('selected_days_count')->nullable();
            // $table->foreignUuid('batch_type_id')->constrained('batch_types');
            // $table->foreignUuid('batch_slot_id')->constrained('batch_slots');
            // $table->foreignUuid('batch_code_id')->constrained('batch_codes');
            // $table->foreignUuid('location_id')->constrained('locations');
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
        Schema::dropIfExists('batches');
    }
};
