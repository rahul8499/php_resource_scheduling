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
        Schema::create('schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('location_id')->constrained('locations');
            $table->foreignUuid('batch_id')->constrained('batches');
            $table->foreignUuid('faculty_id')->constrained('faculties');
            $table->foreignUuid('subject_id')->constrained('subjects');
            $table->string('slot_time'); // Add the slot_time column
            $table->date('date');
            $table->enum('status', ['draft', 'publish'])->default('draft');
            $table->text('error')->nullable();
            // $table->string('schedule_type')->default('default');
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
        Schema::dropIfExists('schedules');
    }
};
