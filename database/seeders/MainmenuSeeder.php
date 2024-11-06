<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mainmenu as MainmenuDB;
use App\Models\Submenu as SubmenuDB;
use App\Models\PowerAction as PowerActionDB;

class MainmenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $mainmenu = [
            //admin後台
            ['sort' => 1, 'is_on' => 1, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-cogs"></i>', 'name' => '系統管理', 'url' => ''],
            ['sort' => 2, 'is_on' => 1, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-store"></i>', 'name' => '商家管理', 'url' => ''],
            ['sort' => 3, 'is_on' => 1, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-truck"></i>', 'name' => '物流管理', 'url' => ''],
            ['sort' => 4, 'is_on' => 1, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fab fa-product-hunt"></i>', 'name' => '商品管理', 'url' => ''],
            ['sort' => 5, 'is_on' => 1, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-users"></i>', 'name' => '使用者管理', 'url' => ''],
            ['sort' => 6, 'is_on' => 1, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-users"></i>', 'name' => '團購管理', 'url' => ''],
            ['sort' => 7, 'is_on' => 1, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-ad"></i>', 'name' => '行銷策展', 'url' => ''],
            ['sort' => 8, 'is_on' => 1, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-chart-bar"></i>', 'name' => '統計資料', 'url' => ''],
            ['sort' => 9, 'is_on' => 0, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '預留一', 'url' => ''],
            ['sort' => 10, 'is_on' => 0, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '預留二', 'url' => ''],
            ['sort' => 11, 'is_on' => 0, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '預留三', 'url' => ''],
            ['sort' => 12, 'is_on' => 0, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '預留四', 'url' => ''],
            ['sort' => 13, 'is_on' => 0, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '預留五', 'url' => ''],
            ['sort' => 14, 'is_on' => 0, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '預留六', 'url' => ''],
            ['sort' => 15, 'is_on' => 0, 'type' => 1, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '功能測試', 'url' => ''],
            //商家後台
            ['sort' => 1, 'is_on' => 1, 'type' => 2, 'url_type' => 2, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<span class="nav-icon svg-icon svg-icon-2"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.3" d="M18 10V20C18 20.6 18.4 21 19 21C19.6 21 20 20.6 20 20V10H18Z" fill="currentColor" /><path opacity="0.3" d="M11 10V17H6V10H4V20C4 20.6 4.4 21 5 21H12C12.6 21 13 20.6 13 20V10H11Z" fill="currentColor" /><path opacity="0.3" d="M10 10C10 11.1 9.1 12 8 12C6.9 12 6 11.1 6 10H10Z" fill="currentColor" /><path opacity="0.3" d="M18 10C18 11.1 17.1 12 16 12C14.9 12 14 11.1 14 10H18Z" fill="currentColor" /><path opacity="0.3" d="M14 4H10V10H14V4Z" fill="currentColor" /><path opacity="0.3" d="M17 4H20L22 10H18L17 4Z" fill="currentColor" /><path opacity="0.3" d="M7 4H4L2 10H6L7 4Z" fill="currentColor" /><path d="M6 10C6 11.1 5.1 12 4 12C2.9 12 2 11.1 2 10H6ZM10 10C10 11.1 10.9 12 12 12C13.1 12 14 11.1 14 10H10ZM18 10C18 11.1 18.9 12 20 12C21.1 12 22 11.1 22 10H18ZM19 2H5C4.4 2 4 2.4 4 3V4H20V3C20 2.4 19.6 2 19 2ZM12 17C12 16.4 11.6 16 11 16H6C5.4 16 5 16.4 5 17C5 17.6 5.4 18 6 18H11C11.6 18 12 17.6 12 17Z" fill="currentColor" /></svg></span>', 'name' => '商家資料管理', 'url' => '../profile'],
            ['sort' => 2, 'is_on' => 0, 'type' => 2, 'url_type' => 2, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-users"></i>', 'name' => '商家帳號管理', 'url' => '../account'],
            ['sort' => 3, 'is_on' => 1, 'type' => 2, 'url_type' => 2, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-list-ol"></i>', 'name' => '商家商品管理', 'url' => '../product'],
            ['sort' => 4, 'is_on' => 0, 'type' => 2, 'url_type' => 2, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-ad"></i>', 'name' => '商家策展管理', 'url' => '../curation'],
            ['sort' => 5, 'is_on' => 1, 'type' => 2, 'url_type' => 2, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-shopping-cart"></i>', 'name' => 'iCarry採購單', 'url' => '../icarryOrder'],
            ['sort' => 6, 'is_on' => 1, 'type' => 2, 'url_type' => 2, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-tags"></i>', 'name' => '商家出貨管理', 'url' => '../shipping'],
            ['sort' => 7, 'is_on' => 1, 'type' => 2, 'url_type' => 2, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-truck"></i>', 'name' => '順豐運單管理', 'url' => '../sfShipping'],
            //admin後台
            ['sort' => 16, 'is_on' => 0, 'type' => 1, 'url_type' => 2, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-file-export"></i>', 'name' => '匯出中心', 'url' => '../exportcenter'],
            ['sort' => 17, 'is_on' => 0, 'type' => 1, 'url_type' => 2, 'open_window' => 1, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '舊版後台網站', 'url' => 'https://admin.icarry.me'],
            ['sort' => 18, 'is_on' => 0, 'type' => 1, 'url_type' => 2, 'open_window' => 1, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '舊版商家後台', 'url' => 'https://vendor.icarry.me'],
            //中繼系統
            ['sort' => 1, 'is_on' => 1, 'type' => 3, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-cogs"></i>', 'name' => '系統管理', 'url' => ''],
            ['sort' => 3, 'is_on' => 1, 'type' => 3, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-shopping-cart"></i>', 'name' => '訂單管理', 'url' => ''],
            ['sort' => 4, 'is_on' => 1, 'type' => 3, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i>', 'name' => '採購管理', 'url' => ''],
            ['sort' => 5, 'is_on' => 1, 'type' => 3, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-cash-register"></i>', 'name' => '進出貨管理', 'url' => ''],
            ['sort' => 9, 'is_on' => 1, 'type' => 3, 'url_type' => 2, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-file-export"></i>', 'name' => '匯出中心', 'url' => '../exportCenter'],
            ['sort' => 8, 'is_on' => 1, 'type' => 3, 'url_type' => 2, 'open_window' => 0, 'power_action' => 'M,O,E', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i>', 'name' => '排程管理', 'url' => '../schedules'],
            ['sort' => 7, 'is_on' => 1, 'type' => 3, 'url_type' => 2, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i>', 'name' => '信件模板管理', 'url' => '../mailTemplates'],
            ['sort' => 6, 'is_on' => 1, 'type' => 3, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-undo-alt"></i>', 'name' => '銷退折讓管理', 'url' => ''],
            ['sort' => 2, 'is_on' => 1, 'type' => 3, 'url_type' => 0, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-users"></i>', 'name' => '員工人事管理', 'url' => ''],
        ];
        $submenu = [
            [ //系統管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O,EX', 'fa5icon' => '<i class="nav-icon fas fa-users"></i>', 'name' => '管理員帳號管理', 'url' => 'admins'],
                ['sort' => 2, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O,S', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '後台選單管理', 'url' => 'mainmenus'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O,S', 'fa5icon' => '<i class="nav-icon fas fa-globe-americas"></i>', 'name' => '國家資料設定', 'url' => 'countries'],
                ['sort' => 4, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '公司資料設定', 'url' => 'companysettings'],
                ['sort' => 5, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '系統參數設定', 'url' => 'systemsettings'],
                ['sort' => 6, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '提貨日設定', 'url' => 'receiverbase'],
                ['sort' => 7, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O,S', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '付款方式設定', 'url' => 'paymethods'],
                ['sort' => 8, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => 'IP管制設定', 'url' => 'ipSettings'],
                ['sort' => 9, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i>', 'name' => '管理者登入登出紀錄', 'url' => 'adminLoginLog'],
            ],
            [ //商家管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O,EX', 'fa5icon' => '<i class="nav-icon fas fa-list"></i>', 'name' => '商家列表管理', 'url' => 'vendors'],
                ['sort' => 2, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O', 'fa5icon' => '<i class="nav-icon fas fa-store-slash"></i>', 'name' => '商家分店列表', 'url' => 'vendorshops'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O,T', 'fa5icon' => '<i class="nav-icon fas fa-users"></i>', 'name' => '商家帳號列表', 'url' => 'vendoraccounts'],
            ],
            [ //物流管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,S', 'fa5icon' => '<i class="nav-icon fas fa-list-ul"></i>', 'name' => '物流廠商管理', 'url' => 'shippingvendors'],
                ['sort' => 2, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O', 'fa5icon' => '<i class="nav-icon fas fa-shipping-fast"></i>', 'name' => '物流運費設定', 'url' => 'shippingfees'],
                ['sort' => 4, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-shipping-fast"></i>', 'name' => '渠道出貨資訊', 'url' => 'shippinginfo'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M', 'fa5icon' => '<i class="nav-icon fas fa-shipping-fast"></i>', 'name' => '無法派送關鍵字管理', 'url' => 'addressDisable'],
            ],
            [ //商品管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,CP,EX,IM', 'fa5icon' => '<i class="nav-icon fas fa-list-ol"></i>', 'name' => '商品管理', 'url' => 'products'],
                ['sort' => 2, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'EX', 'fa5icon' => '<i class="nav-icon fas fa-archive"></i>', 'name' => '組合商品', 'url' => 'packages'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,S', 'fa5icon' => '<i class="nav-icon fas fa-underline"></i>', 'name' => '單位名稱設定', 'url' => 'unitnames'],
                ['sort' => 4, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,O,S', 'fa5icon' => '<i class="nav-icon fab fa-buromobelexperte"></i>', 'name' => '商品分類設定', 'url' => 'categories'],
                ['sort' => 6, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '提貨日設定', 'url' => 'receiverbase'],
                ['sort' => 5, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,O,S', 'fa5icon' => '<i class="nav-icon fab fa-buromobelexperte"></i>', 'name' => '商品次分類設定', 'url' => 'subCategories'],
                ['sort' => 7, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,D,O', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '商品變價管理', 'url' => 'priceChanges'],
            ],
            [ //使用者管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,O,P,SMS,SMM', 'fa5icon' => '<i class="nav-icon fas fa-users"></i>', 'name' => '使用者管理', 'url' => 'users'],
                ['sort' => 2, 'is_on' => 1, 'url_type' => 2, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-sms"></i>', 'name' => '發送簡訊功能', 'url' => 'sendSMS'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 1, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-comments"></i>', 'name' => '客服訊息平台', 'url' => 'https://app.crisp.chat/initiate/login/'],
            ],
            [ //團購管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,O', 'fa5icon' => '<i class="nav-icon fas fa-users-cog"></i>', 'name' => '團購設定', 'url' => 'groupbuyings'],
            ],
            [ //行銷策展
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,O,S', 'fa5icon' => '<i class="nav-icon fas fa-ad"></i>', 'name' => '首頁策展', 'url' => 'curations'],
                ['sort' => 2, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,O,S', 'fa5icon' => '<i class="nav-icon fas fa-ad"></i>', 'name' => '分類策展', 'url' => 'categorycurations'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,O', 'fa5icon' => '<i class="nav-icon fas fa-bullhorn"></i>', 'name' => '推薦註冊碼設定', 'url' => 'refercodes'],
                ['sort' => 4, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,O', 'fa5icon' => '<i class="nav-icon fas fa-bullhorn"></i>', 'name' => '優惠活動設定', 'url' => 'promoboxes'],
                ['sort' => 5, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,O', 'fa5icon' => '<i class="nav-icon fas fa-bullhorn"></i>', 'name' => '促銷代碼設定', 'url' => 'promocodes'],
                ['sort' => 6, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M', 'fa5icon' => '<i class="nav-icon fas fa-link"></i>', 'name' => '短網址設定', 'url' => 'shortUrl'],
                ['sort' => 7, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,D,O,S', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '搜尋標題設定', 'url' => 'searchtitles'],
                ['sort' => 8, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,D,O,S', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '首頁橫幅圖管理', 'url' => 'indexBanners'],
            ],
            [ //統計資料
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-chart-bar"></i>', 'name' => '註冊人數統計', 'url' => 'usermonthlytotal'],
                ['sort' => 2, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-chart-bar"></i>', 'name' => '訂單每日統計(多)', 'url' => 'orderdailytotal'],
                ['sort' => 3, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-chart-bar"></i>', 'name' => '訂單每月統計(多)', 'url' => 'ordermonthlytotal'],
                ['sort' => 5, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-chart-bar"></i>', 'name' => '訂單區間統計', 'url' => 'intervalstatistics'],
                ['sort' => 6, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-chart-bar"></i>', 'name' => '訂單物流統計', 'url' => 'shippingmonthlytotal'],
                ['sort' => 7, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-chart-bar"></i>', 'name' => '商品銷量統計', 'url' => 'productsales'],
                ['sort' => 8, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-chart-bar"></i>', 'name' => '商家銷量統計', 'url' => 'vendorsales'],
                ['sort' => 9, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-chart-bar"></i>', 'name' => '促銷活動銷量統計', 'url' => 'promostatistics'],
                ['sort' => 4, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-chart-bar"></i>', 'name' => '訂單每月出貨統計', 'url' => 'ordermonthlyselltotal'],
                ['sort' => 2, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-chart-bar"></i>', 'name' => '訂單每日統計', 'url' => 'orderdailytotalOne'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-chart-bar"></i>', 'name' => '訂單每月統計', 'url' => 'ordermonthlytotalOne'],
            ],
            [
                //預留一
            ],
            [
                //預留二
            ],
            [
                //預留三
            ],
            [
                //預留四
            ],
            [
                //預留五
            ],
            [
                //預留六
            ],
            [
                //功能測試
            ],
            [ //商家後台商家管理
                ['sort' => 1, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O,EX', 'fa5icon' => '<i class="nav-icon fas fa-list"></i>', 'name' => '商家資料管理', 'url' => 'profile'],
                ['sort' => 2, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O', 'fa5icon' => '<i class="nav-icon fas fa-store-slash"></i>', 'name' => '商家分店管理', 'url' => 'shop'],
                ['sort' => 3, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O,T', 'fa5icon' => '<i class="nav-icon fas fa-users"></i>', 'name' => '商家帳號管理', 'url' => 'account'],
            ],
            [ //商家後台商品管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,EX', 'fa5icon' => '<i class="nav-icon fas fa-list-ol"></i>', 'name' => '商品管理', 'url' => 'product'],
                ['sort' => 2, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'EX', 'fa5icon' => '<i class="nav-icon fas fa-archive"></i>', 'name' => '組合商品', 'url' => 'package'],
                ['sort' => 3, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'EX', 'fa5icon' => '<i class="nav-icon fas fa-ad"></i>', 'name' => '行銷策展', 'url' => 'curation'],
            ],
            [ //商家後台訂單管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,EX,IM,PR,MK,CO,RM,PP', 'fa5icon' => '<i class="nav-icon fas fa-cart-arrow-down"></i>', 'name' => '待出貨訂單', 'url' => 'waittingShipping'],
                ['sort' => 2, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'MK,CO', 'fa5icon' => '<i class="nav-icon fas fa-cart-arrow-down"></i>', 'name' => '已出貨訂單', 'url' => 'finishedOrder'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'NE,DE,M,EX,IM,PR', 'fa5icon' => '<i class="nav-icon far fa-check-square"></i>', 'name' => '已取消訂單', 'url' => 'canceledOrder'],
            ],
            [ //商家後台 iCarryGo 管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M', 'fa5icon' => '<i class="nav-icon fas fa-users"></i>', 'name' => '機台管理', 'url' => 'machine'],
                ['sort' => 2, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M', 'fa5icon' => '<i class="nav-icon fas fa-cart-arrow-down"></i>', 'name' => '訂單管理', 'url' => 'acpayOrder'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M', 'fa5icon' => '<i class="nav-icon far fa-list-alt"></i>', 'name' => '帳務管理', 'url' => 'accounting'],
            ],
            [],
            [],
            [],
            [],
            [],
            [],
            [ //中繼後台
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O', 'fa5icon' => '<i class="nav-icon fas fa-users"></i>', 'name' => '管理員帳號管理', 'url' => 'admins'],
                ['sort' => 2, 'is_on' => 0, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M,O,S', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '後台選單管理', 'url' => 'mainmenus'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '公司資料設定', 'url' => 'companySettings'],
                ['sort' => 4, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '系統參數設定', 'url' => 'systemSettings'],
                ['sort' => 5, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,M', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => 'IP管制設定', 'url' => 'ipSettings'],
                ['sort' => 6, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i>', 'name' => '管理者登入登出紀錄', 'url' => 'adminLoginLog'],
                ['sort' => 7, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i>', 'name' => '會計收款休假日管理', 'url' => 'accountingHoliday'],
                ['sort' => 8, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'O', 'fa5icon' => '<i class="nav-icon fas fa-file-invoice"></i>', 'name' => '開立發票設定', 'url' => 'openInvoiceSetting'],
            ],
            [ //訂單管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,MQ,IM,EX,MK,SY,PR,IMP,IMS,IMT,RM,RT,AL,NI,CI', 'fa5icon' => '<i class="nav-icon fas fa-shopping-cart"></i>', 'name' => '訂單管理', 'url' => 'orders'],
                ['sort' => 6, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-exclamation-circle"></i>', 'name' => '訂單匯入異常', 'url' => 'orderImportAbnormals'],
                ['sort' => 7, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-exclamation-triangle"></i>', 'name' => '訂單取消庫存提示', 'url' => 'orderCancel'],
                ['sort' => 5, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,IM,EX,ST,REO', 'fa5icon' => '<i class="nav-icon fas fa-ticket-alt"></i>', 'name' => '票券管理', 'url' => 'tickets'],
                ['sort' => 8, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-exclamation-triangle"></i>', 'name' => '多筆訂單統計', 'url' => 'chinaOrders'],
                ['sort' => 9, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-money-check"></i>', 'name' => '查詢Asiamiles購買憑証', 'url' => 'asiamilesPrint'],
                ['sort' => 10, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-file-invoice"></i>', 'name' => '發票開立失敗記錄表', 'url' => 'invoiceFailure'],
                ['sort' => 2, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,EX,NI,CI,RM,MK,MQ,AL', 'fa5icon' => '<i class="nav-icon fas fa-shopping-cart"></i>', 'name' => '團購訂單管理', 'url' => 'groupBuyingOrders'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'EX', 'fa5icon' => '<i class="nav-icon fas fa-shopping-cart"></i>', 'name' => '錢街串接訂單管理', 'url' => 'acOrders'],
                ['sort' => 4, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'EX', 'fa5icon' => '<i class="nav-icon fas fa-shopping-cart"></i>', 'name' => '你訂串接訂單管理', 'url' => 'nidinOrders'],
            ],
            [ //採購管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,CO,IM,MK,SY,EX,SEM,COL', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i>', 'name' => '採購單管理', 'url' => 'purchases'],
                ['sort' => 2, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,D', 'fa5icon' => '<i class="nav-icon fas fa-shopping-cart"></i>', 'name' => '採購排除商品管理', 'url' => 'excludeProducts'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,M,CO', 'fa5icon' => '<i class="nav-icon fas fa-warehouse"></i>', 'name' => '折抵單/退貨單管理', 'url' => 'returnDiscounts'],
                ['sort' => 4, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D,DL,CO,SEM', 'fa5icon' => '<i class="nav-icon fas fa-money-check-alt"></i>', 'name' => '對帳單管理', 'url' => 'statements'],
                ['sort' => 5, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '特殊廠商管理', 'url' => 'specialVendors'],
                ['sort' => 6, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '商品貨號轉換', 'url' => 'productTransfer'],
                ['sort' => 7, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'N,D', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '自動入庫商品管理', 'url' => 'autoStockinProduct'],
                ['sort' => 8, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,D', 'fa5icon' => '<i class="nav-icon fas fa-tools"></i>', 'name' => '訂單取消排除商品', 'url' => 'orderCancelProduct'],
                ['sort' => 9, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => '', 'fa5icon' => '<i class="nav-icon fas fa-truck"></i>', 'name' => '商家出貨單資料', 'url' => 'vendorShipping'],
            ],
            [ //進出貨管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,D,IM', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i>', 'name' => '出貨單管理', 'url' => 'sell'],
                ['sort' => 2, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-exclamation-circle"></i></i>', 'name' => '出貨單異常', 'url' => 'sellAbnormals'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-exclamation-circle"></i></i>', 'name' => '進貨單異常', 'url' => 'stockinAbnormals'],
                ['sort' => 4, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,D,E', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i></i>', 'name' => '廠商直寄資料管理', 'url' => 'vendorSellImports'],
                ['sort' => 5, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'D', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i></i>', 'name' => '倉庫出貨資料管理', 'url' => 'warehouseSellImports'],
                ['sort' => 6, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'CO', 'fa5icon' => '<i class="nav-icon fas fa-truck"></i></i>', 'name' => '順豐運單資料管理', 'url' => 'sfShippings'],
            ],
            [],
            [],
            [],
            [ //銷退折讓管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,D', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i>', 'name' => '銷退折讓單管理', 'url' => 'sellReturn'],
                ['sort' => 2, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,IM', 'fa5icon' => '<i class="nav-icon fas fa-exclamation-triangle"></i>', 'name' => '銷退單品庫存提示', 'url' => 'sellReturnInfo'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M', 'fa5icon' => '<i class="nav-icon fas fa-exclamation-triangle"></i>', 'name' => '調撥異常提示', 'url' => 'requisitionAbnormal'],
            ],
            [ //員工人事管理
                ['sort' => 1, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,D,IM', 'fa5icon' => '<i class="nav-icon fas fa-id-card"></i>', 'name' => '員工資料', 'url' => 'employees'],
                ['sort' => 2, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,D,IM', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i>', 'name' => '員工打卡紀錄', 'url' => 'attendances'],
                ['sort' => 3, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,D,IM', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i>', 'name' => '員工加班紀錄', 'url' => 'overtimes'],
                ['sort' => 4, 'is_on' => 1, 'url_type' => 1, 'open_window' => 0, 'power_action' => 'M,D,IM', 'fa5icon' => '<i class="nav-icon fas fa-clipboard-list"></i>', 'name' => '員工請假紀錄', 'url' => 'vacations'],
            ],
        ];

        if (env('DB_MIGRATE_MAINMENUS')) {
            $s1 = $s2 = $s3 = 0;
            for ($i=0;$i<count($mainmenu);$i++) {
                if ($mainmenu[$i]['type'] == 1) {
                    $s1++;
                    $sort = $s1;
                }elseif($mainmenu[$i]['type'] == 2){
                    $s2++;
                    $sort = $s2;
                }else{
                    $s3++;
                    $sort = $s3;
                }
                MainmenuDB::create([
                    'type' => $mainmenu[$i]['type'],
                    'code' => 'M'.($i+1).'S0',
                    'name' => $mainmenu[$i]['name'],
                    'fa5icon' => $mainmenu[$i]['fa5icon'],
                    'power_action' => $mainmenu[$i]['power_action'],
                    'url' => $mainmenu[$i]['url'],
                    'url_type' => $mainmenu[$i]['url_type'],
                    'open_window' => $mainmenu[$i]['open_window'],
                    'is_on' => $mainmenu[$i]['is_on'],
                    'sort' => $mainmenu[$i]['sort'],
                ]);
                if ($mainmenu[$i]['url_type']==0) {
                    if(!empty($submenu[$i])){
                        for ($j=0;$j<count($submenu[$i]);$j++) {
                            if (env('DB_MIGRATE_SUBMENUS')) {
                                SubmenuDB::create([
                                    'mainmenu_id' => $i+1,
                                    'code' => 'M'.($i+1).'S'.($j+1),
                                    'name' => $submenu[$i][$j]['name'],
                                    'fa5icon' => $submenu[$i][$j]['fa5icon'],
                                    'power_action' => $submenu[$i][$j]['power_action'],
                                    'url' => $submenu[$i][$j]['url'],
                                    'url_type' => $submenu[$i][$j]['url_type'],
                                    'open_window' => $submenu[$i][$j]['open_window'],
                                    'is_on' => $submenu[$i][$j]['is_on'],
                                    'sort' => $submenu[$i][$j]['sort'],
                                ]);
                            }
                        }
                    }
                }
            }
            echo "後台選單建立完成\n";
        }


        $PowerActions = [
            ['name' => '新增', 'code' => 'N'],
            ['name' => '刪除', 'code' => 'D'],
            ['name' => '開立', 'code' => 'NE'],
            ['name' => '作廢', 'code' => 'DE'],
            ['name' => '修改', 'code' => 'M'],
            ['name' => '取消', 'code' => 'CO'],
            ['name' => '複製', 'code' => 'CP'],
            ['name' => '修改數量', 'code' => 'MQ'],
            ['name' => '上線/架、啟用', 'code' => 'O'],
            ['name' => '排序', 'code' => 'S'],
            ['name' => '匯入', 'code' => 'IM'],
            ['name' => '匯出', 'code' => 'EX'],
            ['name' => '審查', 'code' => 'C'],
            ['name' => '執行', 'code' => 'E'],
            ['name' => '傳送門', 'code' => 'T'],
            ['name' => '購物金', 'code' => 'P'],
            ['name' => '其他', 'code' => 'X'],
            ['name' => '同步', 'code' => 'SY'],
            ['name' => '下載', 'code' => 'DL'],
            ['name' => '發送簡訊', 'code' => 'SMS'],
            ['name' => '發送訊息', 'code' => 'SMM'],
            ['name' => '發送信件', 'code' => 'SEM'],
            ['name' => '註記', 'code' => 'MK'],
            ['name' => '列印', 'code' => 'PR'],
            ['name' => '發退款信', 'code' => 'RM'],
            ['name' => '批次修改', 'code' => 'IMP'],
            ['name' => '物流匯入', 'code' => 'IMS'],
            ['name' => '訂單在途存貨', 'code' => 'IMT'],
            ['name' => '指定結案', 'code' => 'COL'],
            ['name' => '退貨處理', 'code' => 'RT'],
            ['name' => '折讓金處理', 'code' => 'AL'],
            ['name' => '結帳', 'code' => 'ST'],
            ['name' => '開立發票', 'code' => 'NI'],
            ['name' => '作廢發票', 'code' => 'CI'],
            ['name' => '重開票券', 'code' => 'REO'],
        ];

        if (env('DB_MIGRATE_POWER_ACTIONS')) {
            PowerActionDB::insert($PowerActions);
            echo "Power Actions 建立完成\n";
        }
    }
}
