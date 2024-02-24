<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterBatchesTableAddSelectedDaysCount extends Migration
{
    public function up()
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->integer('selected_days_count')->nullable();
        });
    }

    public function down()
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('selected_days_count');
        });
    }
}
