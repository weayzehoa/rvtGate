<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellAbnormalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SELL_ABNORMALS')) {
            Schema::create('sell_abnormals', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('import_no')->nullable()->comment('匯入號碼');
                $table->bigInteger('order_id')->nullable()->comment('訂單id');
                $table->bigInteger('order_number')->nullable()->comment('訂單號碼');
                $table->bigInteger('erp_order_number')->nullable()->comment('erp訂單號碼');
                $table->unsignedInteger('product_model_id')->nullable()->comment('商品 model id');
                $table->string('product_name')->nullable()->comment('商品名稱');
                $table->integer('order_quantity')->nullable()->comment('訂單數量');
                $table->integer('quantity')->nullable()->comment('異常數量');
                $table->boolean('direct_shipment')->nullable()->default(0)->comment('是否直寄，0:否，1:是');
                $table->boolean('is_chk')->nullable()->default(0)->comment('是否處理，0:否，1:是');
                $table->dateTime('sell_date')->nullable()->comment('出貨日期');
                $table->string('shipping_memo')->nullable()->comment('物流資訊');
                $table->string('memo',500)->nullable()->comment('異常原因');
                $table->dateTime('chk_date')->nullable()->comment('處理日期');
                $table->integer('admin_id')->nullable()->comment('處理人');
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
        if (env('DB_MIGRATE_SELL_ABNORMALS')) {
            Schema::dropIfExists('sell_abnormals');
        }
    }
}
