<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule as ScheduleDB;
use App\Models\SystemSetting as SystemSettingDB;
use App\Models\CompanySetting as CompanySettingDB;
use App\Models\SpecialVendor as SpecialVendorDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\IpAddress as IpAddressDB;
use App\Models\MailTemplate as MailTemplateDB;
use App\Models\iCarryLanguagePack as LanguagePackDB;
use DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (env('DB_MIGRATE_SYSTEM_SETTINGS')) {
            SystemSettingDB::create([
                'exchange_rate_RMB' => 4.34,
                'exchange_rate_SGD' => 21.80,
                'exchange_rate_MYR' => 7.30,
                'exchange_rate_HKD' => 3.75,
                'exchange_rate_USD' => 30.35,
                'sms_supplier' => 'twilio',
                'payment_supplier' => '藍新',
                'email_supplier' => 'aws',
                'invoice_supplier' => 'ezpay',
                'customer_service_supplier' => 'crisp',
                'admin_id' => 40,
                'twpay_quota' => 100042,
                'gross_weight_rate' => 1.3,
            ]);
            echo "System Setting 建立完成\n";
        }

        if (env('DB_MIGRATE_COMPANY_SETTINGS')) {
            CompanySettingDB::create([
                'name' => '直流電通股份有限公司',
                'name_en' => 'Direct Current Co., Ltd.',
                'tax_id_num' => '46452701',
                'tel' => '+886-2-2508-2891',
                'fax' => '+886-2-2508-2892',
                'address' => '台灣台北市中山區南京東路三段103號11樓之1',
                'address_en' => 'Rm. 1, 11F., No. 103, Sec. 3, Nanjing E. Rd., Zhongshan Dist., Taipei City 104507, Taiwan (R.O.C.)',
                'service_tel' => '+886-906-486688',
                'service_email' => 'icarry@icarry.me',
                'url' => 'https://icarry.me/',
                'website' => 'icarry.me',
                'fb_url' => 'https://www.facebook.com/icarryme',
                'Instagram_url' => 'https://www.instagram.com/icarrytaiwan/',
                'Telegram_url' => 'https://t.me/icarryme',
                'line' => '',
                'wechat' => '',
                'admin_id' => 40,
            ]);
            echo "COMPANY Setting 建立完成\n";
        }

        if (env('DB_MIGRATE_SCHEDULES')) {
            $schedules = [
                ['name' => '商品資料更新', 'code' => 'productUpdate', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'everyFiveMinutes'],
                ['name' => '鼎新客戶資料更新至iCarry', 'code' => 'erpCustomerUpdateToIcarry', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'daily'],
                ['name' => '鼎新報價單資料更新至中繼', 'code' => 'erpQuotationUpdate', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'everyFiveMinutes'],
                // ['name' => '訂單物流資料更新至中繼', 'code' => 'orderShippingUpdate', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'hourly'],
                ['name' => '清除匯出中心3天前資料及檔案', 'code' => 'cleanExportFile', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'daily'],
                ['name' => '鼎新採購單同步至中繼', 'code' => 'erpPurchaseSyncToGate', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'daily'],
                ['name' => '訂單自動開立發票', 'code' => 'OrderOpenInvoice', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'everyFiveMinutes'],
                ['name' => '訂單發票更新至鼎新', 'code' => 'OrderInvoiceUpdate', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'daily'],
                ['name' => '廠商直寄資料排程處理', 'code' => 'VendorShippingSellData', 'admin_id' => 40, 'is_on' => 0, 'is_loop' => 1, 'frequency' => 'daily'],
                ['name' => '鼎新商品類別更新至iCarry', 'code' => 'erpProductCategoryUpdateToIcarry', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'daily'],
                ['name' => '票券出貨結帳排程', 'code' => 'ticketSellSettle', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'everyFiveMinutes'],
                ['name' => '票券狀態檢查排程', 'code' => 'checkTicketStatus', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'daily'],
                ['name' => '檢查信件模板', 'code' => 'checkMailTemplate', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'everyFiveMinutes'],
                ['name' => '順豐運單狀態檢查', 'code' => 'checkSFShippingStatus', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'hourly'],
                ['name' => '熱門商品設定排程', 'code' => 'hotProductSetting', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'daily'],
                ['name' => '商品價格變動排程', 'code' => 'productPriceChange', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'hourly'],
                ['name' => '檢查發票數量排程', 'code' => 'checkInvoiceCount', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'daily'],
                ['name' => '檢查AcOrder處理排程', 'code' => 'checkAcOrderProcess', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'everyFiveMinutes'],
                ['name' => '順豐Token更新排程', 'code' => 'sfTokenRenew', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'hourly'],
                ['name' => '檢查NidinOrder處理排程', 'code' => 'checkNidinOrderProcess', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'everyFiveMinutes'],
                ['name' => '匯率更新排程', 'code' => 'getCurrency', 'admin_id' => 40, 'is_on' => 1, 'is_loop' => 1, 'frequency' => 'weekly'],
            ];
            for ($i=0;$i<count($schedules);$i++) {
                ScheduleDB::create([
                    'name' => $schedules[$i]['name'],
                    'code' => $schedules[$i]['code'],
                    'admin_id' => $schedules[$i]['admin_id'],
                    'is_on' => $schedules[$i]['is_on'],
                    'frequency' => $schedules[$i]['frequency'],
                ]);
            }
        }

        if (env('DB_MIGRATE_MAIL_TEMPLATES')) {
            $mailTemplates = [
                ['admin_id' => 40, 'name' => '採購單通知廠商信件(一般)', 'subject' => "【#^#today 採購通知】iCarry_#^#companyName(#^#vendorName)", 'content' => null, 'file' => 'purchaseMailBodyForNormal', 'filename' => 'purchaseMailBodyForNormal.blade.php'],
                ['admin_id' => 40, 'name' => '採購單通知廠商信件(特殊廠商)', 'subject' => "【#^#today 採購通知】iCarry_#^#companyName(#^#vendorName)", 'content' => null, 'file' => 'purchaseMailBodyForSpecialVendor', 'filename' => 'purchaseMailBodyForSpecialVendor.blade.php'],
                ['admin_id' => 40, 'name' => '對帳單通知廠商信件', 'subject' => "【#^#year年#^#month月 對帳單】直流電通-iCarry_#^#companyName(#^#vendorName)", 'content' => null, 'file' => 'StatementMailBody', 'filename' => 'StatementMailBody.blade.php'],
                ['admin_id' => 40, 'name' => '一般訂單通知信件', 'subject' => "iCarry 訂單出貨通知", 'content' => null, 'file' => 'NormalOrderMailBody', 'filename' => 'NormalOrderMailBody.blade.php'],
                ['admin_id' => 40, 'name' => '機場提貨訂單通知信件', 'subject' => "iCarry 訂單出貨通知", 'content' => null, 'file' => 'AirportPickupOrderMailBody', 'filename' => 'AirportPickupOrderMailBody.blade.php'],
                ['admin_id' => 40, 'name' => '亞洲萬里通訂單憑證信件', 'subject' => "iCarry 亞洲萬里通 購買憑証通知", 'content' => null, 'file' => 'AsiamileOrderMailBody', 'filename' => 'AsiamileOrderMailBody.blade.php'],
                ['admin_id' => 40, 'name' => '亞洲萬里通機場提貨訂單憑證信件', 'subject' => "iCarry 亞洲萬里通 購買憑証通知", 'content' => null, 'file' => 'AsiamileAirportPickupOrderMailBody', 'filename' => 'AsiamileAirportPickupOrderMailBody.blade.php'],
                ['admin_id' => 40, 'name' => '退款通知信件', 'subject' => "iCarry訂單退款通知 ##^#orderNumber", 'content' => null, 'file' => 'RefunMailBody', 'filename' => 'RefunMailBody.blade.php'],
                ['admin_id' => 40, 'name' => 'KLOOK訂單通知信件', 'subject' => "iCarry_Klook Order Shipment Notice 訂單出貨通知", 'content' => null, 'file' => 'KlookOrderMailBody', 'filename' => 'KlookOrderMailBody.blade.php'],
                ['admin_id' => 40, 'name' => '(新版)採購單通知廠商信件(一般)', 'subject' => "【#^#today 採購通知】iCarry_#^#companyName(#^#vendorName)", 'content' => null, 'file' => 'purchaseMailBodyForNormalNew', 'filename' => 'purchaseMailBodyForNormalNew.blade.php'],
                ['admin_id' => 40, 'name' => '(新版)採購單通知廠商信件(特殊廠商)', 'subject' => "【#^#today 採購通知】iCarry_#^#companyName(#^#vendorName)", 'content' => null, 'file' => 'purchaseMailBodyForSpecialVendorNew', 'filename' => 'purchaseMailBodyForSpecialVendorNew.blade.php'],
                ['admin_id' => 40, 'name' => '採購單修改通知廠商信件', 'subject' => "【#^#today 採購異動通知】iCarry_#^#companyName(#^#vendorName)", 'content' => null, 'file' => 'purchaseModifyMailBody', 'filename' => 'purchaseModifyMailBody.blade.php'],
                ['admin_id' => 40, 'name' => '團購訂單出貨通知信件(發給團購主)', 'subject' => "iCarry 團購訂單出貨通知", 'content' => null, 'file' => 'GroupBuyOrderSellMailBody', 'filename' => 'GroupBuyOrderSellMailBody.blade.php'],
                ['admin_id' => 40, 'name' => '團購訂單通知信件(發給購買者)', 'subject' => "iCarry 團購訂單出貨通知", 'content' => null, 'file' => 'GroupBuyOrderMailBody', 'filename' => 'GroupBuyOrderMailBody.blade.php'],
                ['admin_id' => 40, 'name' => '團購退款通知信件', 'subject' => "iCarry 團購訂單退款通知 ##^#orderNumber", 'content' => null, 'file' => 'GroupBuyRefunMailBody', 'filename' => 'GroupBuyRefunMailBody.blade.php'],
            ];
            for ($i=0;$i<count($mailTemplates);$i++) {
                MailTemplateDB::create([
                    'admin_id' => $mailTemplates[$i]['admin_id'],
                    'name' => $mailTemplates[$i]['name'],
                    'subject' => $mailTemplates[$i]['subject'],
                    'content' => $mailTemplates[$i]['content'],
                    'file' => $mailTemplates[$i]['file'],
                    'filename' => $mailTemplates[$i]['filename'],
                ]);
            }
        }

        if (env('DB_MIGRATE_COMPANY_SETTINGS')) {
            CompanySettingDB::create([
                'name' => '直流電通股份有限公司',
                'name_en' => 'Direct Current Co., Ltd.',
                'tax_id_num' => '46452701',
                'tel' => '+886-2-2508-2891',
                'fax' => '+886-2-2508-2892',
                'address' => '台灣台北市中山區南京東路三段103號11樓之1',
                'address_en' => 'Rm. 1, 11F., No. 103, Sec. 3, Nanjing E. Rd., Zhongshan Dist., Taipei City 104507, Taiwan (R.O.C.)',
                'service_tel' => '+886-906-486688',
                'service_email' => 'icarry@icarry.me',
                'url' => 'https://icarry.me/',
                'website' => 'icarry.me',
                'fb_url' => 'https://www.facebook.com/icarryme',
                'Instagram_url' => 'https://www.instagram.com/icarrytaiwan/',
                'Telegram_url' => 'https://t.me/icarryme',
                'line' => '',
                'wechat' => '',
                'admin_id' => 40,
            ]);
            echo "COMPANY Setting 建立完成\n";
        }

        if (env('DB_MIGRATE_SPECIAL_VENDORS')) {
            //特殊廠商id
            $v = ['589','594','600','601','607','616','620','623','624','635','636','622','583'];
            $vendors = VendorDB::whereIn('id',$v)->get();
            foreach($vendors as $vendor){
                SpecialVendorDB::create([
                    'vendor_id' => $vendor->id,
                    'code' => 'A'.str_pad($vendor->id,5,"0",STR_PAD_LEFT),
                    'company' => $vendor->company,
                    'name' => $vendor->name,
                ]);
            }
            echo "特殊廠商列表 建立完成\n";
        }

        if (env('DB_MIGRATE_IP_ADDRESSES')) {
            //特殊廠商id
            $ips = [
                ['ip' => '::1', 'admin_id' => 0, 'memo' => 'LocalHost', 'disable' => 1, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '127.0.0.1', 'admin_id' => 0, 'memo' => 'LocalHost', 'disable' => 1, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '60.248.153.34', 'admin_id' => 0, 'memo' => '公司固定IP', 'disable' => 1, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '60.248.153.35', 'admin_id' => 0, 'memo' => '公司固定IP', 'disable' => 1, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '60.248.153.36', 'admin_id' => 0, 'memo' => '公司固定IP', 'disable' => 1, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '218.161.46.206', 'admin_id' => 40, 'memo' => 'Roger 家中固定IP', 'disable' => 1, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '180.218.84.41', 'admin_id' => 0, 'memo' => null, 'disable' => 0, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '182.233.148.95', 'admin_id' => 0, 'memo' => null, 'disable' => 0, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '114.43.112.19', 'admin_id' => 0, 'memo' => null, 'disable' => 0, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '220.141.86.155', 'admin_id' => 0, 'memo' => null, 'disable' => 0, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '118.150.10.151', 'admin_id' => 0, 'memo' => null, 'disable' => 0, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '118.165.14.132', 'admin_id' => 0, 'memo' => null, 'disable' => 0, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '118.165.24.56', 'admin_id' => 0, 'memo' => null, 'disable' => 0, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '118.165.20.53', 'admin_id' => 0, 'memo' => null, 'disable' => 0, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '220.134.8.43', 'admin_id' => 0, 'memo' => null, 'disable' => 0, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '1.200.97.116', 'admin_id' => 0, 'memo' => null, 'disable' => 0, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '111.240.105.22', 'admin_id' => 0, 'memo' => null, 'disable' => 0, 'created_at' => date('Y-m-d H:i:s')],
                ['ip' => '52.198.65.215', 'admin_id' => 0, 'memo' => null, 'disable' => 0, 'created_at' => date('Y-m-d H:i:s')],
            ];
            IpAddressDB::insert($ips);
            echo "ip列表 建立完成\n";
        }

        if (env('DB_MIGRATE_ICARRY_LANGUAGE_PACKS')) {
            $oldJSLangs = DB::connection('icarryLang')->table('language_js')->orderBy('id','asc')->get();
            $i=1;
            $data = [];
            foreach ($oldJSLangs as $oldJSLang) {
                empty($oldJSLang->key_value) ? $oldJSLang->key_value = $i : '';
                $data[] = [
                    'key_value' => $oldJSLang->key_value,
                    'tw' => $oldJSLang->tw,
                    'en' => $oldJSLang->en,
                    'jp' => $oldJSLang->jp,
                    'kr' => $oldJSLang->kr,
                    'th' => $oldJSLang->th,
                    'memo' => $oldJSLang->memo,
                    'created_at' => $oldJSLang->update_time,
                ];
                $i++;
            }
            $chunks = array_chunk($data, 5000);
            foreach($chunks as $chunk){
                LanguagePackDB::insert($chunk);
            }
            $data = [];
            $x = $i;
            $oldLangs = DB::connection('icarryLang')->table('language_pack')->orderBy('id','asc')->get();
            foreach ($oldLangs as $oldLang) {
                $chk = LanguagePackDB::where('tw', $oldLang->tw)->first();
                if (empty($chk)) {
                    $data[] = [
                        'key_value' => $x,
                        'tw' => $oldLang->tw,
                        'en' => $oldLang->en,
                        'jp' => $oldLang->jp,
                        'kr' => $oldLang->kr,
                        'th' => $oldLang->th,
                        'memo' => $oldLang->memo,
                        'created_at' => $oldLang->update_time,
                    ];
                    $x++;
                }
            }
            $newLangs = [
                ['key_value' => 'finished', 'tw' => '已完成', 'en' => 'Finished', 'jp' => 'Finished', 'kr' => 'Finished', 'th' => 'Finished'],
                ['key_value' => 'wayForDelivery', 'tw' => '待出貨', 'en' => 'Wait for delivery', 'jp' => 'Wait for delivery', 'kr' => 'Wait for delivery', 'th' => 'Wait for delivery'],
                ['key_value' => 'handsFree', 'tw' => '免自提', 'en' => 'Hands-free', 'jp' => 'Hands-free', 'kr' => 'Hands-free', 'th' => 'Hands-free'],
                ['key_value' => 'tripleInvoice', 'tw' => '三聯式', 'en' => 'Triple invoice', 'jp' => 'Triple invoice', 'kr' => 'Triple invoice', 'th' => 'Triple invoice'],
                ['key_value' => 'doubleInvoice', 'tw' => '二聯式', 'en' => 'Double invoice', 'jp' => 'Double invoice', 'kr' => 'Double invoice', 'th' => 'Double invoice'],
                ['key_value' => 'receiptOfDonationCharityFoundation', 'tw' => '收據捐贈：慈善基金會', 'en' => 'Receipt of donation: Charity Foundation', 'jp' => 'Receipt of donation: Charity Foundation', 'kr' => 'Receipt of donation: Charity Foundation', 'th' => 'Receipt of donation: Charity Foundation'],
                ['key_value' => 'receiptOfDonationCharityFoundation', 'tw' => '收據捐贈：慈善基金會', 'en' => 'Receipt of donation: Charity Foundation', 'jp' => 'Receipt of donation: Charity Foundation', 'kr' => 'Receipt of donation: Charity Foundation', 'th' => 'Receipt of donation: Charity Foundation'],
                ['key_value' => 'barcodeCarrierForNaturalPersonCertificate', 'tw' => '自然人憑證條碼載具', 'en' => 'Barcode carrier for natural person certificate', 'jp' => 'Barcode carrier for natural person certificate', 'kr' => 'Barcode carrier for natural person certificate', 'th' => 'Barcode carrier for natural person certificate'],
                ['key_value' => 'ezPayCarrier', 'tw' => '智付寶載具', 'en' => 'ezPay carrier', 'jp' => 'ezPay carrier', 'kr' => 'ezPay carrier', 'th' => 'ezPay carrier'],
            ];
            for($i=0;$i<count($newLangs);$i++){
                $data[] = [
                    'key_value' => $newLangs[$i]['key_value'],
                    'tw' => $newLangs[$i]['tw'],
                    'en' => $newLangs[$i]['en'],
                    'jp' => $newLangs[$i]['jp'],
                    'kr' => $newLangs[$i]['kr'],
                    'th' => $newLangs[$i]['th'],
                    'memo' => null,
                    'created_at' => now(),
                ];
            }
            $chunks = array_chunk($data, 5000);
            foreach($chunks as $chunk){
                LanguagePackDB::insert($chunk);
            }
            echo "Language Pack 遷移完成\n";
        }
    }
}
