<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

use App\Exports\Sheets\EmptySheet;
use App\Exports\Sheets\StatementExportSheet;
use App\Exports\Sheets\NidinStatementExportSheet;
use App\Exports\Sheets\NidinReturnStatementExportSheet;
use App\Exports\Sheets\NidinSetBalanceStatementExportSheet;
use App\Exports\Sheets\MoneyStreeStatementExportSheet;
use App\Models\Statement as StatementDB;

class StatementExport implements WithMultipleSheets, WithProperties
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
        if(!empty($param['vendor'])){
            $vendor = $param['vendor'];
            if(in_array($vendor->id,[729,730])){
                //找出今日建立對帳單的最後一筆單號
                $tmp = StatementDB::where('statement_no','>=',date('ymd').'00001')->select('statement_no')->orderBy('statement_no','desc')->first();
                !empty($tmp) ? $param['statementNo'] = $tmp->statement_no + 1 : $param['statementNo'] = date('ymd').'00001';
                $sheets = [
                    new NidinStatementExportSheet($param),
                    new NidinReturnStatementExportSheet($param),
                    new NidinSetBalanceStatementExportSheet($param),
                ];
            }elseif(in_array($vendor->id,[717])){
                $sheets = [
                    new MoneyStreeStatementExportSheet($this->param)
                ];
            }else{
                $sheets = [
                    new StatementExportSheet($this->param)
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
