<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_EMPLOYEE_ATTENDANCES_TABLE')) {
            Schema::create('employee_attendances', function (Blueprint $table) {
                $table->id();
                $table->string('employee_no')->index()->comment('工號');
                $table->date('work_date')->comment('打卡日期');
                $table->boolean('week')->comment('星期幾');
                $table->dateTime('chk_time')->comment('打卡時間');
                $table->string('result')->comment('打卡結果');
                $table->string('memo')->comment('打卡備註');
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
        if (env('DB_MIGRATE_EMPLOYEE_ATTENDANCES_TABLE')) {
            Schema::dropIfExists('employee_attendances');
        }
    }
}
