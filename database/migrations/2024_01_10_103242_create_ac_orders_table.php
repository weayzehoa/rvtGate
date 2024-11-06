<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_AC_ORDERS_TABLE')) {
            Schema::create('ac_orders', function (Blueprint $table) {
                $table->id();
                $table->string('serial_no')->comment('ac交易序號');
                $table->longText('get_json')->nullable();
                $table->string('message')->nullable()->comment('訊息');
                $table->bigInteger('order_id')->index()->nullable()->comment('訂單id');
                $table->bigInteger('order_number')->index()->nullable()->comment('訂單號碼');
                $table->integer('amount')->nullable()->comment('訂單金額');
                $table->boolean('is_invoice')->nullable()->default(0)->comment('是否開發票，0:否，1:是');
                $table->boolean('is_sync')->nullable()->default(0)->comment('是否同步，0:否，1:是');
                $table->bigInteger('purchase_id')->index()->nullable()->comment('採購單id');
                $table->bigInteger('purchase_no')->index()->nullable()->comment('採購單號');
                $table->boolean('purchase_sync')->nullable()->default(0)->comment('採購是否同步，0:否，1:是');
                $table->boolean('is_sell')->nullable()->default(0)->comment('是否銷貨，0:否，1:是');
                $table->boolean('is_stockin')->nullable()->default(0)->comment('是否入庫，0:否，1:是');
                $table->string('ip',20)->comment('IP Address');
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
        if (env('DB_MIGRATE_AC_ORDERS_TABLE')) {
            Schema::dropIfExists('ac_orders');
        }
    }
}
