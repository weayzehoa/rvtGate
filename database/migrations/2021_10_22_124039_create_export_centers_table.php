<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExportCentersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_EXPORT_CENTERS')) {
            Schema::create('export_centers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('export_no')->comment('匯出編號');
                $table->unsignedInteger('admin_id')->nullable()->default(null)->comment('管理者id');
                $table->string('cate')->comment('類別');
                $table->string('name')->comment('名稱');
                $table->longText('condition')->nullable()->comment('條件');
                $table->string('filename')->nullable()->comment('檔案名稱');
                $table->dateTime('start_time')->nullable()->comment('開始時間');
                $table->dateTime('end_time')->nullable()->comment('結束時間');
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
        if (env('DB_MIGRATE_EXPORT_CENTERS')) {
            Schema::dropIfExists('export_centers');
        }
    }
}
