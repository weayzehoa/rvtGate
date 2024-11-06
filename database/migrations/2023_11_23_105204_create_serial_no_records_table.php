<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSerialNoRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SERIAL_NO_RECORDS_TABLE')) {
            Schema::create('serial_no_records', function (Blueprint $table) {
                $table->id();
                $table->string('type')->index()->comment('流水號類型');
                $table->string('serial_no')->nullable()->index()->comment('序列號');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (env('DB_MIGRATE_SERIAL_NO_RECORDS_TABLE')) {
            Schema::dropIfExists('serial_no_records');
        }
    }
}
