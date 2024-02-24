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
        Schema::create('faculties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string("first_name");
            $table->string("faculty_code");
            $table->string("last_name");
            $table->text("mail");
            $table->string("phone");
            $table->string("gender");
            $table->string("address");
            // $table->string("subject");
            $table->string("age");
            $table->string("experience");
            $table->string('image')->nullable();
            // $table->string("batch_code");
            // $table->foreignUuid('batch_type_id')->nullable(false)->constrained('batch_types');
            // $table->foreignUuid('batch_slot_id')->constrained('batch_slots');
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
        Schema::dropIfExists('faculties');
    }
};
