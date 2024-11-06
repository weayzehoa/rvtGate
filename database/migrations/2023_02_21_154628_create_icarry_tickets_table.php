<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIcarryTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ICARRY_TICKETS_TABLE')) {
            Schema::connection('icarry')->create('tickets', function (Blueprint $table) {
                $table->id();
                $table->string('create_type')->nullable()->comment('渠道類別');
                $table->binary('ticket_no')->comment('票券號碼');
                $table->string('ticket_order_no')->comment('券商訂單編號');
                $table->string('platform_no')->comment('券商代號');
                $table->integer('vendor_id')->comment('商家代號');
                $table->string('vendor_name')->comment('票券號碼');
                $table->integer('product_id')->comment('商品代號');
                $table->string('product_name')->comment('商品名稱');
                $table->integer('product_model_id')->comment('商品model代號');
                $table->string('digiwin_no')->comment('鼎新貨號');
                $table->string('sku')->comment('商品貨號');
                $table->integer('order_id')->nullable()->comment('訂單id');
                $table->bigInteger('order_number')->nullable()->comment('訂單號碼');
                $table->integer('order_item_id')->nullable()->comment('訂單商品id');
                $table->string('partner_order_number')->nullable()->comment('外渠訂單號碼');
                $table->dateTime('used_time')->nullable()->comment('核銷時間');
                $table->boolean('status')->default(0)->comment('狀態 -1作廢 0未銷售 1已銷售 9已核銷');
                $table->string('purchase_no')->nullable()->comment('採購單號');
                $table->date('purchase_date')->nullable()->comment('採購日期');
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
        if (env('DB_MIGRATE_ICARRY_TICKETS_TABLE')) {
            Schema::connection('icarry')->dropIfExists('tickets');
        }
    }
}
