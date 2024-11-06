<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ADMINS')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('account')->unique()->comment('帳號');
                $table->string('name')->comment('姓名');
                $table->string('email')->comment('電子郵件');
                $table->string('password')->nullable()->comment('密碼');
                $table->text('power')->nullable()->comment('選單權限');
                $table->boolean('is_on')->unsigned()->default(1)->comment('啟用'); //1 為啟用 0 為停用
                $table->boolean('lock_on')->unsigned()->default(0)->comment('鎖定');
                $table->string('mobile',20)->nullable()->comment('電話號碼');
                $table->string('otp',6)->nullable()->comment('驗證碼');
                $table->dateTime('otp_time')->nullable()->comment('驗證碼到期時間');
                $table->dateTime('off_time')->nullable()->comment('時間');
                $table->rememberToken();
                $table->timestamps();
                //使用軟刪除
                $table->softDeletes();
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
        if (env('DB_MIGRATE_ADMINS')) {
            Schema::dropIfExists('admins');
        }
    }
}
