<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderImportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ORDER_IMPORTS')) {
            Schema::create('order_imports', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('import_no')->comment('匯入號碼');
                $table->string('type')->comment('匯入類別');
                $table->string('digiwin_payment_id')->nullable()->comment('客戶代碼');
                $table->string('partner_order_number')->nullable()->comment('客戶訂單號碼');
                $table->dateTime('create_time')->nullable()->comment('訂單建立時間');
                $table->dateTime('pay_time')->nullable()->comment('付款時間');
                $table->string('receiver_address')->nullable()->comment('收件人地址');
                $table->string('receiver_name')->nullable()->comment('收件人');
                $table->string('receiver_tel')->nullable()->comment('收件人電話');
                $table->string('receiver_email')->nullable()->comment('收件人郵件');
                $table->string('user_memo')->nullable()->comment('客戶備註');
                $table->string('receiver_keyword')->nullable()->comment('航班飯店名稱');
                $table->string('receiver_key_time')->nullable()->comment('航班時間旅店提貨時間');
                $table->string('shipping_method')->nullable()->comment('寄送方式代碼');
                $table->string('sku')->nullable()->comment('品號');
                $table->integer('quantity')->nullable()->comment('數量');
                $table->decimal('price',10,2)->nullable()->comment('單價');
                $table->date('book_shipping_date')->nullable()->comment('預定出貨日');
                $table->boolean('status')->nullable()->comment('訂單狀態');
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
        if (env('DB_MIGRATE_ORDER_IMPORTS')) {
            Schema::dropIfExists('order_imports');
        }
    }
}
