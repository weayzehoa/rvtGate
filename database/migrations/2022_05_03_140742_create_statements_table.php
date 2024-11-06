<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_STATEMENTS')) {
            Schema::create('statements', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('statement_no')->comment('對帳單號');
                $table->integer('vendor_id')->comment('商家id');
                $table->integer('amount')->comment('金額');
                $table->integer('stockin_price')->comment('進貨金額');
                $table->integer('return_price')->comment('退貨金額');
                $table->integer('discount_price')->comment('折抵金額');
                $table->date('invoice_date')->comment('發票收受日');
                $table->date('start_date')->comment('對帳起始日');
                $table->date('end_date')->comment('對帳結束日');
                $table->dateTime('notice_time')->nullable()->comment('通知廠商時間');
                $table->longText('purchase_nos')->comment('purchase order numbers');
                $table->longText('purchase_item_ids')->comment('purchase order item ids');
                $table->longText('return_discount_ids')->comment('return discount ids');
                $table->string('filename')->comment('檔案名稱');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否取消，0:否，1:是');
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
        if (env('DB_MIGRATE_STATEMENTS')) {
            Schema::dropIfExists('statements');
        }
    }
}
