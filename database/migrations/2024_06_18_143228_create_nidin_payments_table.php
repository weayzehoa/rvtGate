<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNidinPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_NIDIN_PAYMENTS_TABLE')) {
            Schema::create('nidin_payments', function (Blueprint $table) {
                $table->id();
                $table->longText('get_json')->nullable();
                $table->string('notify_url')->nullable()->comment('金流通知');
                $table->string('nidin_order_no')->nullable()->comment('商家訂單編號');
                $table->string('transaction_id')->nullable()->comment('金流序號');
                $table->integer('amount')->nullable()->comment('付款金額');
                $table->boolean('is_success')->nullable()->default(0)->comment('是否成功，0:否，1:是');
                $table->string('message')->nullable()->comment('訊息');
                $table->string('ip',20)->nullable()->comment('IP Address');
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
        if (env('DB_MIGRATE_NIDIN_PAYMENTS_TABLE')) {
            Schema::dropIfExists('nidin_payments');
        }
    }
}
