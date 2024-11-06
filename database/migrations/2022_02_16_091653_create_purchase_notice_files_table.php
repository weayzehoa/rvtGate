<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseNoticeFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_PURCHASE_NOTICE_FILES')) {
            Schema::create('purchase_notice_files', function (Blueprint $table) {
                $table->id();
                $table->integer('export_no')->nullable()->comment('匯出單號');
                $table->bigInteger('purchase_no')->nullable()->comment('採購單號');
                $table->string('type')->nullable()->comment('類型');
                $table->string('filename')->nullable()->comment('檔案名稱');
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
        if (env('DB_MIGRATE_PURCHASE_NOTICE_FILES')) {
            Schema::dropIfExists('purchase_notice_files');
        }
    }
}
