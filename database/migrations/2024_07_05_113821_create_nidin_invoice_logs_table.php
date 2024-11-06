<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNidinInvoiceLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_NIDIN_INVOICE_LOGS_TABLE')) {
            Schema::create('nidin_invoice_logs', function (Blueprint $table) {
                $table->id();
                $table->string('type')->nullable()->comment('處理類型');
                $table->string('nidin_order_no')->index()->nullable()->comment('商家訂單編號');
                $table->longText('param')->nullable();
                $table->boolean('is_success')->nullable()->default(0)->comment('是否成功，0:否，1:是');
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
        if (env('DB_MIGRATE_NIDIN_INVOICE_LOGS_TABLE')) {
            Schema::dropIfExists('nidin_invoice_logs');
        }
    }
}
