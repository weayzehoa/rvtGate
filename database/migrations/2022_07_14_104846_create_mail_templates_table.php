<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_MAIL_TEMPLATES')) {
            Schema::create('mail_templates', function (Blueprint $table) {
                $table->id();
                $table->integer('admin_id')->comment('管理者');
                $table->string('name')->comment('名稱');
                $table->string('subject')->nullable()->comment('信件標題');
                $table->longText('content')->nullable()->comment('信件內容');
                $table->string('file')->comment('檔案名稱(無附檔名)');
                $table->string('filename')->comment('檔案名稱');
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
        if (env('DB_MIGRATE_MAIL_TEMPLATES')) {
            Schema::dropIfExists('mail_templates');
        }
    }
}
