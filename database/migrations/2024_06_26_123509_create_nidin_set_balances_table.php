<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNidinSetBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_NIDIN_SET_BALANCES_TABLE')) {
            Schema::create('nidin_set_balances', function (Blueprint $table) {
                $table->id();
                $table->string('set_no')->nullable()->comment('套票號碼');
                $table->integer('set_qty')->nullable()->comment('套票數量');
                $table->integer('balance')->nullable()->comment('剩下票券金額');
                $table->integer('remain')->nullable()->comment('AC退票後留下餘額');
                $table->boolean('is_close')->nullable()->default(0)->comment('是否，0:否，1:是');
                $table->boolean('is_lock')->nullable()->default(0)->comment('是否，0:否，1:是');
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
        if (env('DB_MIGRATE_NIDIN_SET_BALANCES_TABLE')) {
            Schema::dropIfExists('nidin_set_balances');
        }
    }
}
