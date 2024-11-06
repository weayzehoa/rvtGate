<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_COMPANY_SETTINGS')) {
            Schema::create('company_settings', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable()->comment('公司名稱');
                $table->string('name_en')->nullable()->comment('公司英文名稱');
                $table->string('tax_id_num')->nullable()->comment('統一編號');
                $table->string('tel')->nullable()->comment('公司電話');
                $table->string('fax')->nullable()->comment('公司傳真');
                $table->string('address')->nullable()->comment('公司地址');
                $table->string('address_en')->nullable()->comment('公司英文地址');
                $table->string('service_tel')->nullable()->comment('客服電話');
                $table->string('service_email')->nullable()->comment('客服信箱');
                $table->string('website')->nullable()->comment('官網網址');
                $table->string('url')->nullable()->comment('網站網址含http');
                $table->string('fb_url')->nullable()->comment('FB粉絲頁連結');
                $table->string('Instagram_url')->nullable()->comment('Instagram粉絲頁連結');
                $table->string('Telegram_url')->nullable()->comment('Telegram粉絲頁連結');
                $table->string('line')->nullable()->comment('line號碼');
                $table->string('wechat')->nullable()->comment('wechat號碼');
                $table->unsignedInteger('admin_id')->comment('管理員id');
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
        if (env('DB_MIGRATE_COMPANY_SETTINGS')) {
            Schema::dropIfExists('company_settings');
        }
    }
}
