<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncedInvoiceLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SYNCED_INVOICE_LOGS')) {
            Schema::create('synced_invoice_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('order_id')->comment('訂單id');
                $table->string('order_number',16)->comment('訂單號碼');
                $table->string('erp_order_no',11)->comment('erp訂單編號');
                $table->string('invoice_no',11)->comment('發票號碼');
                $table->dateTime('invoice_time')->comment('發票時間');
                $table->integer('invoice_price')->comment('發票金額');
                $table->integer('invoice_tax')->comment('發票稅額');
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
        if (env('DB_MIGRATE_SYNCED_INVOICE_LOGS')) {
            Schema::dropIfExists('synced_invoice_logs');
        }
    }
}
