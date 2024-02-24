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
        Schema::create('users', function (Blueprint $table) {
        $table->id();
        // $table->uuid('id')->primary();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('contact_number')->nullable();
        $table->foreignUuid('location_id')->constrained('locations');
        $table->string('password')->nullable();
        $table->string('confirmation_token')->nullable();
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
        Schema::dropIfExists('users');
    }
};
