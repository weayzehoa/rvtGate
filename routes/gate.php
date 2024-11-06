<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//後台 gate 用的路由 網址看起來就像 https://gate.localhost/{名稱}
use App\Http\Controllers\Gate\GateLoginController;
use App\Http\Controllers\Gate\DashboardController;
use App\Http\Controllers\Gate\AdminsController;
use App\Http\Controllers\Gate\MainMenusController;
use App\Http\Controllers\Gate\SubMenusController;
use App\Http\Controllers\Gate\AdminLoginLogController;
use App\Http\Controllers\Gate\OrdersController;
use App\Http\Controllers\Gate\PurchasesController;
use App\Http\Controllers\Gate\PurchaseExcludeController;
use App\Http\Controllers\Gate\ExportCenterController;
use App\Http\Controllers\Gate\SellController;
use App\Http\Controllers\Gate\SchedulesController;
use App\Http\Controllers\Gate\OrderShippingsController;
use App\Http\Controllers\Gate\OrderVendorShippingsController;
use App\Http\Controllers\Gate\ReturnDiscountController;
use App\Http\Controllers\Gate\StatementController;
use App\Http\Controllers\Gate\SpecialVendorsController;
use App\Http\Controllers\Gate\SystemSettingsController;
use App\Http\Controllers\Gate\CompanySettingsController;
use App\Http\Controllers\Gate\VendorConfirmController;
use App\Http\Controllers\Gate\IpAddressController;
use App\Http\Controllers\Gate\SellAbnormalController;
use App\Http\Controllers\Gate\MailTemplateController;
use App\Http\Controllers\Gate\StockinAbnormalController;
use App\Http\Controllers\Gate\OrderImportAbnormalController;
use App\Http\Controllers\Gate\VendorSellImportController;
use App\Http\Controllers\Gate\WarehouseSellImportController;
use App\Http\Controllers\Gate\OrderCancelController;
use App\Http\Controllers\Gate\ProductTransferController;
use App\Http\Controllers\Gate\NewFunctionController;
use App\Http\Controllers\Gate\SellReturnController;
use App\Http\Controllers\Gate\SellReturnInfoController;
use App\Http\Controllers\Gate\RequisitionAbnormalController;
use App\Http\Controllers\Gate\TicketsController;
use App\Http\Controllers\Gate\AutoStockinProductController;
use App\Http\Controllers\Gate\ChinaOrdersController;
use App\Http\Controllers\Gate\AsiamilesPrintController;
use App\Http\Controllers\Gate\InvoiceFailureController;
use App\Http\Controllers\Gate\VendorShippingController;
use App\Http\Controllers\Gate\OrderCancelExcludeController;
use App\Http\Controllers\Gate\SFShippingController;
use App\Http\Controllers\Gate\EmployeesController;
use App\Http\Controllers\Gate\EmployeesVacationController;
use App\Http\Controllers\Gate\EmployeesOvertimeController;
use App\Http\Controllers\Gate\EmployeesAttendanceController;
use App\Http\Controllers\Gate\GroupBuyingOrdersController;
use App\Http\Controllers\Gate\AcOrderController;
use App\Http\Controllers\Gate\NidinOrderController;
use App\Http\Controllers\Gate\NidinPaymentController;
use App\Http\Controllers\Gate\AccountingHolidayController;
use App\Http\Controllers\Gate\OpenInvoiceSettingController;

//檢查IP
Route::middleware(['checkIp'])->group(function () {
    Route::name('gate.')->group(function() {
        Route::get('login', [GateLoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [GateLoginController::class, 'login'])->name('login.submit');
        Route::get('otp', [GateLoginController::class, 'showOtpForm'])->name('otp');
        Route::post('otp', [GateLoginController::class, 'otp'])->name('otp.submit');
        Route::get('2fa', [GateLoginController::class, 'show2faForm'])->name('2fa');
        Route::post('2fa', [GateLoginController::class, 'verify2fa'])->name('2fa.submit');
        Route::get('passwordChange', [GateLoginController::class, 'showPwdChangeForm'])->name('passwordChange');
        Route::post('passwordChange', [GateLoginController::class, 'passwordChange'])->name('passwordChange.submit');
        Route::get('logout', [GateLoginController::class, 'logout'])->name('logout');
        Route::get('', [GateLoginController::class, 'showLoginForm']);
        Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

        //管理員帳號管理功能
        Route::post('admins/unlock/{id}', [AdminsController::class, 'unlock'])->name('admins.unlock');
        Route::post('admins/active/{id}', [AdminsController::class, 'active']);
        Route::get('admins/search', [AdminsController::class, 'search']);
        Route::get('admins/changePassWord', [AdminsController::class, 'changePassWordForm']);
        Route::post('admins/changePassWord', [AdminsController::class, 'changePassWord'])->name('admins.changePassWord');
        Route::resource('admins', AdminsController::class);

        //後台主選單管理功能
        Route::post('mainmenus/active/{id}', [MainMenusController::class, 'active']);
        Route::post('mainmenus/open/{id}', [MainMenusController::class, 'open']);
        Route::get('mainmenus/sortup/{id}',[MainMenusController::class, 'sortup']);
        Route::get('mainmenus/sortdown/{id}',[MainMenusController::class, 'sortdown']);
        Route::get('mainmenus/submenu/{id}',[MainMenusController::class, 'submenu']);
        Route::resource('mainmenus', MainMenusController::class);

        //後台次選單管理功能
        Route::post('submenus/active/{id}', [SubMenusController::class, 'active']);
        Route::post('submenus/open/{id}', [SubMenusController::class, 'open']);
        Route::get('submenus/sortup/{id}',[SubMenusController::class, 'sortup']);
        Route::get('submenus/sortdown/{id}',[SubMenusController::class, 'sortdown']);
        Route::resource('submenus', SubMenusController::class);

        //管理者登入登出紀錄
        Route::resource('adminLoginLog', AdminLoginLogController::class);

        //訂單管理
        Route::post('orders/allowance', [OrdersController::class, 'allowance'])->name('orders.allowance');
        Route::post('orders/getAllowanceItem', [OrdersController::class, 'getAllowanceItem'])->name('orders.getAllowanceItem');
        Route::post('orders/getInvoiceLogs', [OrdersController::class, 'getInvoiceLogs'])->name('orders.getInvoiceLogs');
        Route::post('orders/markNotPurchase', [OrdersController::class, 'markNotPurchase'])->name('orders.markNotPurchase');
        Route::post('orders/import', [OrdersController::class, 'import'])->name('orders.import');
        Route::post('orders/purchaseCancel', [OrdersController::class, 'purchaseCancel'])->name('orders.purchaseCancel');
        Route::post('orders/getPurchasedItems', [OrdersController::class, 'getPurchasedItems'])->name('orders.getPurchasedItems');
        Route::post('orders/getUnPurchase', [OrdersController::class, 'getUnPurchase'])->name('orders.getUnPurchase');
        Route::post('orders/getInfo', [OrdersController::class, 'getInfo'])->name('orders.getInfo');
        Route::post('orders/getlog', [OrdersController::class, 'getLog'])->name('orders.getlog');
        Route::post('orders/modify', [OrdersController::class, 'modify'])->name('orders.modify');
        Route::post('orders/multiProcess', [OrdersController::class, 'multiProcess'])->name('orders.multiProcess');
        Route::post('orders/getshippingvendors', [OrdersController::class, 'getShippingVendors'])->name('orders.getshippingvendors');
        Route::post('orders/getvendors', [OrdersController::class, 'getVendors'])->name('orders.getvendors');
        Route::get('orders/getExpressData', [OrdersController::class, 'getExpressData'])->name('orders.getexpressdata');
        Route::resource('orders', OrdersController::class);
        Route::resource('ordershippings', OrderShippingsController::class);
        Route::resource('ordervendorshippings', OrderVendorShippingsController::class);

        Route::post('orderImportAbnormals/deleteAll', [OrderImportAbnormalController::class, 'deleteAll'])->name('orderImportAbnormals.deleteAll');
        Route::resource('orderImportAbnormals', OrderImportAbnormalController::class);

        //採購單管理
        Route::post('purchases/dateModify', [PurchasesController::class, 'dateModify'])->name('purchases.dateModify');
        Route::post('purchases/stockinModify', [PurchasesController::class, 'stockinModify'])->name('purchases.stockinModify');
        Route::post('purchases/getStockin', [PurchasesController::class, 'getStockin'])->name('purchases.getStockin');
        Route::post('purchases/removeOrder', [PurchasesController::class, 'removeOrder'])->name('purchases.removeOrder');
        Route::post('purchases/qtyModify', [PurchasesController::class, 'qtyModify'])->name('purchases.qtyModify');
        Route::post('purchases/itemmemo', [PurchasesController::class, 'itemMemo'])->name('purchases.itemmemo');
        Route::post('purchases/import', [PurchasesController::class, 'import'])->name('purchases.import');
        Route::post('purchases/notice', [PurchasesController::class, 'notice'])->name('purchases.notice');
        Route::post('purchases/getChangeLog', [PurchasesController::class, 'getChangeLog'])->name('purchases.getChangeLog');
        Route::post('purchases/getlog', [PurchasesController::class, 'getLog'])->name('purchases.getlog');
        Route::post('purchases/cancel', [PurchasesController::class, 'cancel'])->name('purchases.cancel');
        Route::post('purchases/close', [PurchasesController::class, 'close'])->name('purchases.close');
        Route::post('purchases/sync', [PurchasesController::class, 'sync'])->name('purchases.sync');
        Route::post('purchases/multiProcess', [PurchasesController::class, 'multiProcess'])->name('purchases.multiProcess');
        Route::resource('purchases', PurchasesController::class);

        //採購排除商品
        Route::post('excludeProducts/getProducts', [PurchaseExcludeController::class, 'getProducts']);
        Route::resource('excludeProducts', PurchaseExcludeController::class);

        //匯出中心
        Route::post('exportCenter/download', [ExportCenterController::class, 'download'])->name('exportCenter.download');
        Route::resource('exportCenter', ExportCenterController::class);

        //退庫紀錄管理
        Route::get('returnForm/{id}', [PurchasesController::class, 'returnForm'])->name('purchases.returnForm');
        Route::post('productReturn/{id}', [PurchasesController::class, 'productReturn'])->name('purchases.productReturn');

        Route::post('returnDiscounts/getProducts', [ReturnDiscountController::class, 'getProducts'])->name('returnDiscounts.getProducts');
        Route::post('returnDiscounts/cancel', [ReturnDiscountController::class, 'cancel'])->name('returnDiscounts.cancel');
        Route::resource('returnDiscounts', ReturnDiscountController::class);

        //排程管理
        Route::post('schedules/execNow/{id}', [SchedulesController::class, 'execNow'])->name('schedules.execNow');
        Route::post('schedules/active/{id}', [SchedulesController::class, 'active'])->name('schedules.active');
        Route::resource('schedules', SchedulesController::class);

        //對帳單管理
        Route::post('statements/cancel', [StatementController::class, 'cancel'])->name('statements.cancel');
        Route::post('statements/multiProcess', [StatementController::class, 'multiProcess'])->name('statements.multiProcess');
        Route::resource('statements', StatementController::class);

        //出貨單管理
        Route::post('sell/modifyDate', [SellController::class, 'modifyDate'])->name('sell.modifyDate');
        Route::post('sell/cancel', [SellController::class, 'cancel'])->name('sell.cancel');
        Route::post('sell/import', [SellController::class, 'import'])->name('sell.import');
        Route::resource('sell', SellController::class);

        //出貨異常
        Route::resource('sellAbnormals', SellAbnormalController::class);

        //進貨異常
        Route::resource('stockinAbnormals', StockinAbnormalController::class);

        //特殊廠商管理
        Route::resource('specialVendors', SpecialVendorsController::class);

        Route::resource('autoStockinProduct', AutoStockinProductController::class);

        //Company Settings 設定
        Route::resource('companySettings', CompanySettingsController::class);

        //System Settings 設定
        Route::resource('systemSettings', SystemSettingsController::class);

        //IP Settings 設定
        Route::post('ipSettings/active/{id}', [IpAddressController::class, 'active']);
        Route::resource('ipSettings', IpAddressController::class);

        //信件模板管理
        Route::resource('mailTemplates', MailTemplateController::class);

        //廠商直寄資料管理
        Route::post('vendorSellImports/multiProcess', [VendorSellImportController::class, 'multiProcess'])->name('vendorSellImports.multiProcess');
        Route::post('vendorSellImports/executeImport', [VendorSellImportController::class, 'executeImport'])->name('vendorSellImports.executeImport');
        Route::post('vendorSellImports/delete', [VendorSellImportController::class,'delete'])->name('vendorSellImports.delete');
        Route::resource('vendorSellImports', VendorSellImportController::class);

        //倉庫入庫資料管理
        Route::post('warehouseSellImports/delete', [WarehouseSellImportController::class,'delete'])->name('warehouseSellImports.delete');
        Route::resource('warehouseSellImports', WarehouseSellImportController::class);

        //修改訂單數量及取消訂單
        Route::post('orderCancel/process', [OrderCancelController::class, 'process'])->name('orderCancel.process');
        Route::resource('orderCancel', OrderCancelController::class);

        //貨號轉換
        Route::resource('productTransfer', ProductTransferController::class);

        //功能開發測試
        Route::resource('newFunction', NewFunctionController::class);

        //銷退折讓單管理
        Route::post('sellReturn/cancel', [SellReturnController::class, 'cancel'])->name('sellReturn.cancel');
        Route::resource('sellReturn', SellReturnController::class);

        //銷退單品庫存提示
        Route::post('sellReturnInfo/import', [SellReturnInfoController::class, 'import'])->name('sellReturnInfo.import');
        Route::post('sellReturnInfo/multiProcess', [SellReturnInfoController::class, 'multiProcess'])->name('sellReturnInfo.multiProcess');
        Route::post('sellReturnInfo/confirm', [SellReturnInfoController::class, 'confirm'])->name('sellReturnInfo.confirm');
        Route::resource('sellReturnInfo', SellReturnInfoController::class);

        //調撥異常提示
        Route::post('requisitionAbnormal/multiProcess', [RequisitionAbnormalController::class, 'multiProcess'])->name('requisitionAbnormal.multiProcess');
        Route::resource('requisitionAbnormal', RequisitionAbnormalController::class);

        //票券管理
        Route::post('tickets/resend', [TicketsController::class, 'resend'])->name('tickets.resend');
        Route::post('tickets/multiProcess', [TicketsController::class, 'multiProcess'])->name('tickets.multiProcess');
        Route::post('tickets/settle', [TicketsController::class, 'settle'])->name('tickets.settle');
        Route::post('tickets/open', [TicketsController::class, 'open'])->name('tickets.open');
        Route::post('tickets/getInfo', [TicketsController::class, 'getInfo'])->name('tickets.getInfo');
        Route::post('tickets/import', [TicketsController::class, 'import'])->name('tickets.import');
        Route::resource('tickets', TicketsController::class);

        //中國訂單統計
        Route::resource('chinaOrders', ChinaOrdersController::class);

        //查詢Asiamiles購買憑証
        Route::resource('asiamilesPrint', AsiamilesPrintController::class);

        //發票開立失敗記錄表
        Route::resource('invoiceFailure', InvoiceFailureController::class);

        //發票開立失敗記錄表
        Route::resource('vendorShipping', VendorShippingController::class);

        //訂單取消排除商品
        Route::post('orderCancelProduct/getProducts', [OrderCancelExcludeController::class, 'getProducts']);
        Route::resource('orderCancelProduct', OrderCancelExcludeController::class);

        //順豐物流資料管理
        Route::post('sfShippings/getStatus', [SFShippingController::class, 'getStatus']);
        Route::resource('sfShippings', SFShippingController::class);

        //員工資料
        Route::post('employees/import', [EmployeesController::class, 'import'])->name('employees.import');
        Route::resource('employees', EmployeesController::class);

        //員工請假紀錄
        Route::post('vacations/import', [EmployeesVacationController::class, 'import'])->name('vacations.import');
        Route::resource('vacations', EmployeesVacationController::class);

        //員工加班紀錄
        Route::post('overtimes/import', [EmployeesOvertimeController::class, 'import'])->name('overtimes.import');
        Route::resource('overtimes', EmployeesOvertimeController::class);

        //員工出勤紀錄
        Route::post('attendances/import', [EmployeesAttendanceController::class, 'import'])->name('attendances.import');
        Route::resource('attendances', EmployeesAttendanceController::class);

        //團購訂單管理
        Route::post('groupBuyingOrders/multiProcess', [GroupBuyingOrdersController::class, 'multiProcess'])->name('groupBuyingOrders.multiProcess');
        Route::post('groupBuyingOrders/getlog', [GroupBuyingOrdersController::class, 'getLog'])->name('groupBuyingOrders.getlog');
        Route::post('groupBuyingOrders/modify', [GroupBuyingOrdersController::class, 'modify'])->name('groupBuyingOrders.modify');
        Route::post('groupBuyingOrders/getInfo', [GroupBuyingOrdersController::class, 'getInfo'])->name('groupBuyingOrders.getInfo');
        Route::resource('groupBuyingOrders', GroupBuyingOrdersController::class);

        //交流錢街訂單管理
        Route::resource('acOrders', AcOrderController::class);

        //交流你訂訂單管理
        Route::resource('nidinOrders', NidinOrderController::class);

        //會計休假日管理
        Route::resource('accountingHoliday', AccountingHolidayController::class);

        //開立發票設定
        Route::post('openInvoiceSetting/activeInvoice', [OpenInvoiceSettingController::class, 'activeInvoice'])->name('openInvoiceSetting.activeInvoice');
        Route::post('openInvoiceSetting/activeAcrtb', [OpenInvoiceSettingController::class, 'activeAcrtb'])->name('openInvoiceSetting.activeAcrtb');
        Route::resource('openInvoiceSetting', OpenInvoiceSettingController::class);
    });
});

Route::name('gate.')->group(function() {
    //廠商確認
    Route::resource('vendorConfirm', VendorConfirmController::class);
});

//票券回傳
Route::post('ticketNotify', [TicketsController::class, 'notify'])->name('ticketNotify');
//票券開票
Route::post('openTicket', [TicketsController::class, 'openTicketFromICARRY'])->name('openTicket');

//AC-錢街案
Route::post('acOrder', [AcOrderController::class, 'acOrder'])->name('acOrder');

Route::middleware(['checkThirdpartyIp'])->group(function () {
    //AC-你訂案
    Route::post('nidin/Product', [NidinOrderController::class, 'product'])->name('nidinProduct'); //建立商品
    Route::post('nidin/Order', [NidinOrderController::class, 'order'])->name('nidinOrder'); //建立訂單
    Route::post('nidin/Query', [NidinOrderController::class, 'query'])->name('query'); //查詢
    // Route::post('nidin/OpenTicket', [NidinOrderController::class, 'openTicket'])->name('nidinOpenTicket'); //開票
    Route::post('nidin/WriteOff', [NidinOrderController::class, 'writeOff'])->name('nidinWriteOff'); //核銷
    Route::post('nidin/Return', [NidinOrderController::class, 'return'])->name('nidinReturn'); //消費者退貨
    Route::post('nidin/Invalid', [NidinOrderController::class, 'invalid'])->name('nidinInvalid'); //你訂退貨

    //你訂-金流
    Route::post('nidin/Payment/Pay', [NidinPaymentController::class, 'pay'])->name('nidinPay'); //金流付款
    Route::post('nidin/Payment/Query', [NidinPaymentController::class, 'query'])->name('nidinPayQuery'); //金流付款
    Route::post('nidin/Payment/Capture', [NidinPaymentController::class, 'capture'])->name('nidinPayCapture'); //金流付款
    Route::post('nidin/Payment/Refund', [NidinPaymentController::class, 'refund'])->name('nidinPayRefund'); //金流付款
    Route::post('nidin/Payment/Notify', [NidinPaymentController::class, 'notify'])->name('nidinPayNotify'); //金流付款
});

//你訂-客戶端回傳
Route::get('nidin/Payment/CallBack', [NidinPaymentController::class, 'callback'])->name('nidinPayCallBack'); //金流付款

//管理者IP設定
Route::get('ipsetting', [IpAddressController::class, 'showIPsettingForm'])->name('ipsetting');
Route::post('ipsetting', [IpAddressController::class, 'ipsetting'])->name('ipsetting.submit');
Route::get('ipsetting/sendOtp', [IpAddressController::class, 'showOtpForm'])->name('ipsetting.sendOtp');
Route::post('ipsetting/sendOtp', [IpAddressController::class, 'checkOtp'])->name('ipsetting.checkOtp');
Route::get('ipsetting/2fa', [IpAddressController::class, 'show2faForm'])->name('ipsetting.2fa');
Route::post('ipsetting/2fa', [IpAddressController::class, 'check2fa'])->name('ipsetting.check2fa');

//管理者解除帳號鎖定
Route::get('unlockAccount', [GateLoginController::class, 'showUnlockForm'])->name('unlockAccount');
Route::post('unlockAccount', [GateLoginController::class, 'unlockAccount'])->name('unlockAccount.submit');
Route::get('unlockAccount/sendOtp', [GateLoginController::class, 'showUnlockOtpForm'])->name('unlockAccount.sendOtp');
Route::post('unlockAccount/sendOtp', [GateLoginController::class, 'checkUnlockOtp'])->name('unlockAccount.checkOtp');
Route::get('unlockAccount/2fa', [GateLoginController::class, 'showUnlock2faForm'])->name('unlockAccount.2fa');
Route::post('unlockAccount/2fa', [GateLoginController::class, 'checkUnlock2fa'])->name('unlockAccount.check2fa');

//產生qrCode
Route::get('qrcode', [SystemSettingsController::class, 'qrcode'])->name('qrcode');
