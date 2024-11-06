<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

use App\Exports\Sheets\EmptySheet;
use App\Exports\Sheets\ReferFriendOrdersExportSheet1;
use App\Exports\Sheets\ReferFriendOrdersExportSheet2;
use App\Exports\Sheets\ReferFriendOrdersExportSheet3;
use App\Exports\Sheets\ReferFriendOrdersExportSheet4;

class ReferFriendExport implements WithMultipleSheets, WithProperties
{
    protected $param;

    public function __construct(array $param)
    {
        $this->param = $param;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $param = $this->param;
        $sheets = [];
        if(!empty($param['cate']) && $param['cate'] == 'referFriend'){
            if(isset($param['year']) && isset($param['month'])){
                $param['month'] < 10 && !strstr($param['month'],'0') ? $param['month'] = '0'.$param['month'] : '';
                $param['start'] = $param['year'].'-'.$param['month'].'-01 00:00:00';
                $param['end'] = $param['year'].'-'.$param['month'].'-31 23:59:59';
                $sheets = [
                    new ReferFriendOrdersExportSheet1($param),
                    new ReferFriendOrdersExportSheet2($param),
                    new ReferFriendOrdersExportSheet3($param),
                    new ReferFriendOrdersExportSheet4($param),
                ];
            }
        }else{
            $sheets = [new EmptySheet()];
        }

        return $sheets;
    }

    public function properties(): array
    {
        return [
            'creator'        => 'iCarry系統管理員',
            'lastModifiedBy' => 'iCarry系統管理員',
            'title'          => 'iCarry後台管理-對帳單資料匯出',
            'description'    => 'iCarry後台管理-對帳單資料匯出',
            'subject'        => 'iCarry後台管理-對帳單資料匯出',
            'keywords'       => '',
            'category'       => '',
            'manager'        => 'iCarry系統管理員',
            'company'        => 'iCarry.me 直流電通股份有限公司',
        ];
    }
}
