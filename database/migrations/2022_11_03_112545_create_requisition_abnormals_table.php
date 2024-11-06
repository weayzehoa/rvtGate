<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequisitionAbnormalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_REQUISITION_ABNORMALS')) {
            Schema::create('requisition_abnormals', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('stockin_import_id')->nullable()->comment('匯入id');
                $table->bigInteger('import_no')->nullable()->comment('匯入號碼');
                $table->string('gtin13')->nullable()->comment('條碼或鼎新品號');
                $table->string('product_name')->nullable()->comment('商品名稱');
                $table->integer('quantity')->nullable()->comment('數量');
                $table->dateTime('expiry_date')->nullable()->comment('效期日期');
                $table->dateTime('stockin_date')->nullable()->comment('入庫日期');
                $table->string('memo')->nullable()->comment('異常原因');
                $table->boolean('is_chk')->nullable()->default(0)->comment('是否處理，0:否，1:是');
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
        if (env('DB_MIGRATE_REQUISITION_ABNORMALS')) {
            Schema::dropIfExists('requisition_abnormals');
        }
    }
}
