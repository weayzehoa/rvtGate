<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin as AdminDB;
use App\Models\AdminLoginLog as AdminLoginLogDB;
use App\Models\AdminPwdUpdateLog as AdminPwdUpdateLogDB;
use App\Models\SystemSetting as SystemSettingDB;
use App\Models\CompanySetting as CompanySettingDB;

use DB;
class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (env('DB_MIGRATE_ADMINS')) {
            $data = [];
            $oldAdmins = DB::connection('icarry')->table('admin')->get();
            $c = 1;
            foreach ($oldAdmins as $oldAdmin) {
                if($c == 14){
                    if($oldAdmin->id == 14){
                    }else{
                        $oldAdmins = DB::connection('icarry')->table('admin')->insert([
                            'id' => 14,
                            'account' => 'missing',
                            'name' => '補缺',
                            'email' => 'empty@icarry.me',
                            'pwd' => 'IdontKnow',
                            'create_time' => date('Y-m-d H:i:s'),
                            'admin_power' => null,
                            'lock_on' => 1,
                            'off_time' => null,
                        ]);
                        break;
                    }
                }
                $c++;
            }
            $i = 0;
            $oldAdmins = DB::connection('icarry')->table('admin')->get();
            foreach ($oldAdmins as $oldAdmin) {
                $password = app('hash')->make($oldAdmin->pwd);
                $mainmenuPower = join(',', range(3, 20));
                $submenuPower = join(',', range(1, 50));
                $powerAction = '';
                $power = '';
                //特定管理員的代號, 信成, Chris, Roger, Sam.
                // $spID = array('2','4','40','44');
                $spID = array('2','4','40','44','36','34','29','23','19','49','47','38','42');
                $adminIDs = ['2','4','40'];
                if (in_array($oldAdmin->id, $spID)) {
                    if(in_array($oldAdmin->id,$adminIDs)){
                        $power = 'M26S0,M26S1,M26S1N,M26S1D,M26S1M,M26S1O,M26S3,M26S3M,M26S4,M26S4M,M26S5,M26S5N,M26S5D,M26S5M,M26S6,M27S0,M27S0M,M27S0IM,M27S0EX,M27S0SY,M27S0MK,M27S0PR,M28S0,M28S1,M28S1N,M28S1M,M28S1MK,M28S2,M28S2D,M28S2M,M28S3,M28S3N,M28S3M,M28S4,M28S4N,M28S4D,M28S5,M28S5N,M28S5D,M29S0,M29S1,M29S1D,M29S1M,M29S1IM,M29S2,M29S2M,M29S3,M29S3M,M30S0,M31S0,M31S0M,M31S0O,M31S0E,M32S0,M32S0M';
                    }else{
                        $power = 'M27S0,M27S0M,M27S0EX,M27S0SY,M27S0MK,M27S0PR,M28S0,M28S1,M28S1N,M28S1M,M28S1MK,M28S2,M28S2D,M28S2M,M28S3,M28S3N,M28S3M,M28S4,M28S4N,M28S4D,M28S5,M28S5N,M28S5D,M29S0,M29S1,M29S1D,M29S1M,M29S1IM,M29S2,M29S2D,M29S2M,M29S2IM,M30S0';
                    }
                }
                //帳號應該是唯一，但舊資料裡面有兩個eva，故須將其分離成eva,eva1
                if ($oldAdmin->account == 'eva') {
                    if($i==1){
                        $oldAdmin->account = 'eva'.$i;
                    }
                    $i++;
                }
                $data[] = [
                    'account' => $oldAdmin->account,
                    'name' => $oldAdmin->name,
                    'email' => $oldAdmin->email,
                    'password' => null,
                    'is_on' => $oldAdmin->is_on,
                    'power' => $power,
                    'mobile' => $oldAdmin->mobile,
                    'otp' => $oldAdmin->otp,
                    'otp_time' => $oldAdmin->otp_time,
                    'off_time' => $oldAdmin->off_time,
                    'created_at' => $oldAdmin->create_time,
                ];
            }
            $chunks = array_chunk($data, 5000);
            foreach($chunks as $chunk){
                AdminDB::insert($chunk);
            }
            echo "Admin 遷移完成\n";
        }

        if (env('DB_MIGRATE_ADMIN_LOGIN_LOGS')) {
            //ADMIN LOGIN LOGS 資料移轉
            $subQuery = DB::connection('icarry')->table('admin_login_log');
            $query = DB::query()->fromSub($subQuery, 'alias')->orderBy('alias.id','asc')->chunk(5000, function ($items) {
                $data = [];
                foreach ($items as $item) {
                    $data[] = [
                        'admin_id' => $item->admin_id,
                        'result' => $item->result,
                        'ip' => $item->ip,
                        'account' => $item->account,
                        'created_at' => $item->create_time,
                    ];
                }
                AdminLoginLogDB::insert($data);
            });
            echo "ADMIN LOGIN LOG 遷移完成\n";
        }

        if (env('DB_MIGRATE_ADMIN_PWD_UPDATE_LOGS')) {
            //ADMIN PWD UPDATE LOGS 資料移轉
            $subQuery = DB::connection('icarry')->table('admin_pwd_update_log');
            $query = DB::query()->fromSub($subQuery, 'alias')->orderBy('alias.id','asc')->chunk(5000, function ($items) {
                $data = [];
                foreach ($items as $item) {
                    $data[] = [
                        'admin_id' => $item->admin_id,
                        'ip' => $item->ip,
                        'password' => app('hash')->make($item->pwd),
                        'editor_id' => $item->admin_id,
                        'created_at' => $item->create_time,
                    ];
                }
                AdminPwdUpdateLogDB::insert($data);
            });
            echo "ADMIN LOGIN LOG 遷移完成\n";
        }

    }
}
