<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMainmenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_MAINMENUS')) {
            Schema::create('mainmenus', function (Blueprint $table) {
                $table->id();
                $table->boolean('type')->comment('1:iCarry後台, 2:商家後台');
                $table->string('code')->comment('選單代碼');
                $table->string('name')->comment('選單名稱');
                $table->text('fa5icon')->nullable()->comment('FA5圖示');
                $table->string('power_action')->nullable()->comment('提供的功能');
                $table->string('url')->nullable()->comment('連結位置');
                $table->integer('url_type')->default(0)->comment('連結類型, 0:次選單, 1:內部, 2:外部');
                $table->boolean('open_window')->default(0)->comment('另開視窗, 0:No, 1:Yes');
                $table->boolean('is_on')->default(0)->comment('0:下線, 1:上線');
                $table->float('sort', 11, 1)->default(999999)->comment('排序');
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
        if (env('DB_MIGRATE_MAINMENUS')) {
            Schema::dropIfExists('mainmenus');
        }
    }
}
