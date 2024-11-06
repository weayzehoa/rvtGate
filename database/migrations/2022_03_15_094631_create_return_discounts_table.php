<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_RETURN_DISCOUNTS')) {
            Schema::create('return_discounts', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('return_discount_no')->comment('退貨折抵單號');
                $table->bigInteger('erp_return_discount_no')->comment('erp退貨折抵單號');
                $table->string('type', 10)->comment('類型，A351 退貨, A352 折抵');
                $table->bigInteger('purchase_no')->nullable()->comment('採購單單號');
                $table->integer('vendor_id')->comment('商家id');
                $table->integer('quantity')->comment('總數量');
                $table->decimal('amount',10,2)->comment('總金額');
                $table->decimal('tax',10,2)->comment('稅金');
                $table->date('return_date')->comment('退貨日期');
                $table->string('memo')->nullable()->comment('備註');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否取消，0:否，1:是');
                $table->boolean('is_lock')->nullable()->default(0)->comment('是否鎖定，0:否，1:是');
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
        if (env('DB_MIGRATE_RETURN_DISCOUNTS')) {
            Schema::dropIfExists('return_discounts');
        }
    }
}
