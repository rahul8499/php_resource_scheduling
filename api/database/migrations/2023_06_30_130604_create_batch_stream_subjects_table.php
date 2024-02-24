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
        Schema::create('batch_stream_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('batch_stream_id')->constrained('batch_streams');
            $table->foreignUuid('subject_id')->constrained('subjects');
            $table->timestamps();
            $table->softdeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('batch_stream_subjects');
    }
};
