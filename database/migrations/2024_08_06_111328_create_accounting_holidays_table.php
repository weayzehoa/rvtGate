<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountingHolidaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ACCOUNTING_HOLIDAYS_TABLE')) {
            Schema::create('accounting_holidays', function (Blueprint $table) {
                $table->id();
                $table->string('type')->nullable()->comment('類別');
                $table->date('exclude_date')->nullable()->comment('排除日期');
                $table->string('memo')->nullable()->comment('備註');
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
        if (env('DB_MIGRATE_ACCOUNTING_HOLIDAYS_TABLE')) {
            Schema::dropIfExists('accounting_holidays');
        }
    }
}
