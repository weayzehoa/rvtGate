<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SELLS')) {
            Schema::create('sells', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('sell_no')->comment('銷貨單單號');
                $table->bigInteger('erp_sell_no')->nullable()->comment('erp銷貨單單號');
                $table->unsignedInteger('order_id')->comment('訂單id');
                $table->bigInteger('order_number')->comment('訂單號碼');
                $table->bigInteger('erp_order_number')->comment('erp訂單號碼');
                $table->integer('quantity')->nullable()->comment('總數量');
                $table->decimal('amount',10,2)->nullable()->comment('總金額');
                $table->decimal('tax',10,2)->nullable()->comment('稅金');
                $table->boolean('tax_type')->nullable()->comment('稅別');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否取消，0:否，1:是');
                $table->date('sell_date')->nullable()->comment('銷貨日期');
                $table->string('memo')->nullable()->comment('備註');
                $table->bigInteger('purchase_no')->nullable()->comment('採購單單號');
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
        if (env('DB_MIGRATE_SELLS')) {
            Schema::dropIfExists('sells');
        }
    }
}
