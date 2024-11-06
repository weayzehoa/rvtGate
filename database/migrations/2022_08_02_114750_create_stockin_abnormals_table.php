<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockinAbnormalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_STOCKIN_ABNORMALS')) {
            Schema::create('stockin_abnormals', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('stockin_import_id')->nullable()->comment('匯入號碼id');
                $table->bigInteger('import_no')->nullable()->comment('匯入號碼');
                $table->string('gtin13')->nullable()->comment('條碼或鼎新品號');
                $table->string('product_name')->nullable()->comment('商品名稱');
                $table->integer('purchase_quantity')->nullable()->comment('採購數量');
                $table->integer('need_quantity')->nullable()->comment('需求數量');
                $table->integer('stockin_quantity')->nullable()->comment('入庫數量');
                $table->integer('quantity')->nullable()->comment('異常數量');
                $table->boolean('direct_shipment')->nullable()->default(0)->comment('是否直寄，0:否，1:是');
                $table->boolean('is_chk')->nullable()->default(0)->comment('是否處理，0:否，1:是');
                $table->dateTime('stockin_date')->nullable()->comment('入庫日期');
                $table->string('memo')->nullable()->comment('異常原因');
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
        if (env('DB_MIGRATE_STOCKIN_ABNORMALS')) {
            Schema::dropIfExists('stockin_abnormals');
        }
    }
}
