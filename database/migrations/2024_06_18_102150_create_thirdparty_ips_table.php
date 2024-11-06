<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThirdpartyIpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_THIRDPARTY_IPS_TABLE')) {
            Schema::create('thirdparty_ips', function (Blueprint $table) {
                $table->id();
                $table->string('ip',20)->comment('IP Address');
                $table->string('memo')->nullable()->comment('備註');
                $table->boolean('disable')->default(0)->comment('是否禁用，0:否，1:是');
                $table->boolean('is_on')->unsigned()->default(1)->comment('啟用'); //1 為啟用 0 為停用
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
        if (env('DB_MIGRATE_THIRDPARTY_IPS_TABLE')) {
            Schema::dropIfExists('thirdparty_ips');
        }
    }
}
