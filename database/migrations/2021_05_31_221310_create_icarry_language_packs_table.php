<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIcarryLanguagePacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ICARRY_LANGUAGE_PACKS')) {
            Schema::connection('icarry')->create('language_packs', function (Blueprint $table) {
                $table->id();
                $table->string('key_value',300)->nullable()->comment('索引');
                $table->string('tw',300)->comment('中文');
                $table->string('en',300)->comment('英文');
                $table->string('jp',300)->comment('日文');
                $table->string('kr',300)->comment('韓文');
                $table->string('th',300)->comment('泰文');
                $table->string('memo')->nullable()->comment('說明');
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
        if (env('DB_MIGRATE_ICARRY_LANGUAGE_PACKS')) {
            Schema::dropIfExists('language_packs');
        }
    }
}
