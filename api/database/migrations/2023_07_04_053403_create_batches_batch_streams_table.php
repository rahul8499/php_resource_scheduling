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
        Schema::create('batches_batch_streams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('batch_stream_id')->constrained('batch_streams');
            $table->foreignUuid('batch_id')->constrained('batches');
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
        Schema::dropIfExists('baches_batch_streams');
    }
};
