<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuotationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_QUOTATIONS')) {
            Schema::create('quotations', function (Blueprint $table) {
                $table->id();
                $table->string('MB001', 10)->nullable()->comment('客戶代號');
                $table->string('MB002', 20)->nullable()->comment('品號');
                $table->string('MB003', 4)->nullable()->comment('計價單位');
                $table->string('MB004', 4)->nullable()->comment('幣別');
                $table->integer('MB008')->nullable()->default(0)->comment('單價');
                $table->string('MB017', 8)->nullable()->comment('生效日');
                $table->string('MB018', 8)->nullable()->comment('失效日');
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
        if (env('DB_MIGRATE_QUOTATIONS')) {
            Schema::dropIfExists('quotations');
        }
    }
}
