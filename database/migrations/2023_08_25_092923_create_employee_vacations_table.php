<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeVacationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_EMPLOYEE_VACATIONS_TABLE')) {
            Schema::create('employee_vacations', function (Blueprint $table) {
                $table->id();
                $table->string('approval_no')->comment('審批編號');
                $table->string('employee_no')->index()->comment('工號');
                $table->string('type')->comment('請假類型');
                $table->dateTime('start_time')->comment('開始時間');
                $table->dateTime('end_time')->comment('結束時間');
                $table->decimal('duration',4,1)->comment('時長,小時');
                $table->timestamps();
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
        if (env('DB_MIGRATE_EMPLOYEE_VACATIONS_TABLE')) {
            Schema::dropIfExists('employee_vacations');
        }
    }
}
