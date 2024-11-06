<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use File;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $start = microtime(true); //紀錄開始時間
        if(env('DB_SEED')){
            //清除本地測試的檔案
            File::cleanDirectory(public_path(). '/upload/');
            if(env('DB_SEED_SETTING')){
                $this->call(SettingSeeder::class);
            }else{
                echo "SettingSeeder 已被關閉\n";
            }
            if(env('DB_SEED_ADMIN')){
                $this->call(AdminSeeder::class);
            }else{
                echo "AdminSeeder 已被關閉\n";
            }
            if(env('DB_SEED_MENU')){
                $this->call(MainmenuSeeder::class);
            }else{
                echo "MainmenuSeeder 已被關閉\n";
            }
            if(env('DB_SEED_PRODUCT')){
                $this->call(ProductSeeder::class);
            }else{
                echo "ProductSeeder 已被關閉\n";
            }
            if(env('DB_SEED_ORDER')){
                $this->call(OrderSeeder::class);
            }else{
                echo "OrderSeeder 已被關閉\n";
            }
            if(env('DB_SEED_Purchase')){
                $this->call(PurchaseSeeder::class);
            }else{
                echo "PurchaseSeeder 已被關閉\n";
            }
            $end = microtime(true); //紀錄時間結束
            $duration = $end - $start;
            $hours = (int)($duration/60/60);
            $minutes = (int)($duration/60)-$hours*60;
            $seconds = (int)$duration-$hours*60*60-$minutes*60;
            echo "本次遷移資料，共計 $hours 小時 $minutes 分 $seconds 秒\n";
        }else{
            echo "本功能已被關閉\n";
        }
    }
}
