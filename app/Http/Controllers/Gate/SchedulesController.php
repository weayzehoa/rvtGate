<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule as ScheduleDB;
use App\Jobs\Schedule\ProductUpdateScheduleJob as ProductUpdate;
use App\Jobs\Schedule\CustomerUpdateScheduleJob as CustomerUpdate;
use App\Jobs\Schedule\CleanExportFileScheduleJob as CleanExportFile;
use App\Jobs\Schedule\QuotationUpdateScheduleJob as QuotationUpdate;
use App\Jobs\Schedule\DigiwinPurchaseOrderSynchronizeJob as DigiwinPurchaseOrderSync;
use App\Jobs\Schedule\OrderInvoiceUpdateJob as OrderInvoiceUpdate;
use App\Jobs\Schedule\ErpProductCategoryUpdateToIcarryJob as ErpProductCategoryUpdate;
use App\Jobs\Schedule\CheckMailTemplateScheduleJob as CheckMailTemplate;
use App\Jobs\Schedule\CheckSFShippingStatusScheduleJob as CheckSFShippingStatus;
use App\Jobs\Schedule\HotProductSettingJob as HotProductSetting;
use App\Jobs\Schedule\ProductPriceChangeJob as ProductPriceChange;
use App\Jobs\Schedule\CheckInvoiceCountScheduleJob as CheckInvoiceCount;
use App\Jobs\Schedule\AcOrderProcessScheduleJob;
use App\Jobs\Schedule\NidinOrderProcessScheduleJob;
use App\Jobs\Schedule\SFtokenRenewJob;
use App\Jobs\Schedule\GetCurrencyJob;
use App\Jobs\AdminInvoiceJob;
use App\Jobs\DirectShipmentSellImportJob as SellImportJob;
use App\Jobs\TicketSettleJob;
use App\Jobs\CheckTicketStatusJob;
use Session;

class SchedulesController extends Controller
{
    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $menuCode = 'M31S0';
        $schedules = ScheduleDB::all();
        return view('gate.schedule.index', compact('menuCode','schedules'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $schedule = ScheduleDB::findOrFail($id)->update($request->all());
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    /*
        啟用或禁用
    */
    public function active(Request $request)
    {
        isset($request->is_on) ? $is_on = $request->is_on : $is_on = 0;
        ScheduleDB::findOrFail($request->id)->fill(['is_on' => $is_on])->save();
        return redirect()->back();
    }

    /*
        立即執行
    */
    public function execNow(Request $request,$id)
    {
        $schedule = ScheduleDB::findOrFail($id);
        $param['admin_id'] = auth('gate')->user()->id;
        if(!empty($schedule)){
            switch ($schedule->code) {
                case 'productUpdate':
                    env('APP_ENV') == 'local' ? ProductUpdate::dispatchNow() : ProductUpdate::dispatch();
                    break;
                case 'erpCustomerUpdateToIcarry':
                    env('APP_ENV') == 'local' ? CustomerUpdate::dispatchNow() : CustomerUpdate::dispatch();
                    break;
                case 'erpQuotationUpdate':
                    env('APP_ENV') == 'local' ? QuotationUpdate::dispatchNow() : QuotationUpdate::dispatch();
                    break;
                case 'cleanExportFile':
                    env('APP_ENV') == 'local' ? cleanExportFile::dispatchNow() : cleanExportFile::dispatch();
                    break;
                case 'erpPurchaseSyncToGate':
                    env('APP_ENV') == 'local' ? DigiwinPurchaseOrderSync::dispatchNow() : DigiwinPurchaseOrderSync::dispatch($param);
                    break;
                case 'OrderInvoiceUpdate':
                    env('APP_ENV') == 'local' ? OrderInvoiceUpdate::dispatchNow() : OrderInvoiceUpdate::dispatch();
                    break;
                case 'erpProductCategoryUpdateToIcarry':
                    env('APP_ENV') == 'local' ? ErpProductCategoryUpdate::dispatchNow() : ErpProductCategoryUpdate::dispatch();
                    break;
                case 'VendorShippingSellData':
                    $param['method'] = 'Schedule';
                    $param['type'] = 'directShip';
                    $param['admin_id'] = null;
                    env('APP_ENV') == 'local' ? SellImportJob::dispatchNow() : SellImportJob::dispatch($param);
                    break;
                case 'ticketSellSettle':
                    env('APP_ENV') == 'local' ? TicketSettleJob::dispatchNow() : TicketSettleJob::dispatch();
                    break;
                case 'checkTicketStatus':
                    env('APP_ENV') == 'local' ? CheckTicketStatusJob::dispatchNow() : CheckTicketStatusJob::dispatch();
                    break;
                case 'checkMailTemplate':
                    env('APP_ENV') == 'local' ? CheckMailTemplate::dispatchNow() : CheckMailTemplate::dispatch();
                    break;
                case 'OrderOpenInvoice':
                    $param['model'] = 'OrderOpenInvoice';
                    $param['type'] = 'create';
                    env('APP_ENV') == 'local' ? AdminInvoiceJob::dispatchNow($param) : AdminInvoiceJob::dispatch($param);
                    break;
                case 'checkAcOrderProcess':
                    env('APP_ENV') == 'local' ? AcOrderProcessScheduleJob::dispatchNow() : AcOrderProcessScheduleJob::dispatch();
                    break;
                case 'checkNidinOrderProcess':
                    env('APP_ENV') == 'local' ? NidinOrderProcessScheduleJob::dispatchNow() : NidinOrderProcessScheduleJob::dispatch();
                    break;
                case 'checkSFShippingStatus':
                    env('APP_ENV') == 'local' ? CheckSFShippingStatus::dispatchNow() : CheckSFShippingStatus::dispatch();
                    break;
                case 'hotProductSetting':
                    env('APP_ENV') == 'local' ? HotProductSetting::dispatchNow() : HotProductSetting::dispatch();
                    break;
                case 'productPriceChange':
                    env('APP_ENV') == 'local' ? ProductPriceChange::dispatchNow() : ProductPriceChange::dispatch();
                    break;
                case 'sfTokenRenew':
                    env('APP_ENV') == 'local' ? SFtokenRenewJob::dispatchNow() : SFtokenRenewJob::dispatch();
                    break;
                case 'checkInvoiceCount':
                    env('APP_ENV') == 'local' ? CheckInvoiceCount::dispatchNow() : CheckInvoiceCount::dispatch();
                    break;
                case 'getCurrency':
                    env('APP_ENV') == 'local' ? GetCurrencyJob::dispatchNow() : GetCurrencyJob::dispatch();
                    break;
                }
                $message = "$schedule->name 已開始於背端執行，請稍後一段時間";
                Session::put('success',$message);
            }
            return redirect()->back();
        }
    }
