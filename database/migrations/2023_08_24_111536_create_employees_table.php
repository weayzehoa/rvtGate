<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_EMPLOYEES_TABLE')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable()->comment('姓名');
                $table->string('name_en')->nullable()->comment('英文名');
                $table->string('employee_no')->index()->comment('工號');
                $table->string('department')->nullable()->comment('部門');
                $table->string('title')->nullable()->comment('職稱');
                $table->date('onduty_date')->nullable()->comment('到職日');
                $table->date('leave_date')->nullable()->comment('離職日');
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
        if (env('DB_MIGRATE_EMPLOYEES_TABLE')) {
            Schema::dropIfExists('employees');
        }
    }
}
