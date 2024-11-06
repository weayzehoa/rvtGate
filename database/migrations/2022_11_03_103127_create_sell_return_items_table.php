<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellReturnItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SELL_RETURN_ITEMS')) {
            Schema::create('sell_return_items', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('order_id')->comment('訂單id');
                $table->bigInteger('order_number')->comment('訂單號碼');
                $table->bigInteger('order_item_id')->nullable()->comment('訂單商品id');
                $table->bigInteger('order_item_package_id')->nullable()->comment('訂單組合商品id');
                $table->string('erp_order_type')->nullable()->comment('erp調撥單單別');
                $table->bigInteger('erp_order_no')->nullable()->comment('erp調撥單號碼');
                $table->string('erp_order_sno')->nullable()->comment('erp調撥單序號');
                $table->bigInteger('return_no')->comment('銷退單號碼');
                $table->bigInteger('erp_return_no')->comment('erp銷退單號碼');
                $table->string('erp_return_type')->comment('erp銷退單單別');
                $table->string('erp_return_sno')->comment('erp銷退單序號');
                $table->string('order_digiwin_no')->comment('訂單商品鼎新品號');
                $table->string('origin_digiwin_no')->nullable()->comment('採購單商品鼎新品號');
                $table->integer('quantity')->nullable()->comment('數量');
                $table->integer('return_quantity')->nullable()->comment('實際order_item退貨數量');
                $table->integer('price')->nullable()->comment('單價金額');
                $table->date('expiry_date')->nullable()->comment('效期日期');
                $table->boolean('is_stockin')->nullable()->default(0)->comment('是否入庫，0:否，1:是');
                $table->boolean('direct_shipment')->nullable()->default(0)->comment('是否直寄，0:否，1:是');
                $table->integer('stockin_admin_id')->nullable()->comment('入庫者');
                $table->boolean('is_confirm')->nullable()->default(0)->comment('是否驗收，0:否，1:是');
                $table->bigInteger('erp_requisition_no')->nullable()->comment('erp調撥單號碼');
                $table->string('erp_requisition_sno')->nullable()->comment('erp調撥單序號');
                $table->string('erp_requisition_type')->nullable()->comment('erp調撥單單別');
                $table->string('item_memo')->comment('備註');
                $table->boolean('is_chk')->nullable()->default(0)->comment('是否處理，0:否，1:是');
                $table->dateTime('chk_date')->nullable()->comment('處理日期');
                $table->integer('admin_id')->nullable()->comment('處理人');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否取消，0:否，1:是');
                $table->timestamps();
                // === 索引 ===
                $table->index('order_id');
                $table->index('order_item_id');
                $table->index('return_no');
                $table->index('origin_digiwin_no');
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
        if (env('DB_MIGRATE_SELL_RETURN_ITEMS')) {
            Schema::dropIfExists('sell_return_items');
        }
    }
}
