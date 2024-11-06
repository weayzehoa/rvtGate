<?php

namespace App\Imports;

use App\Models\StockinImport as StockinImportDB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StockinImport implements ToModel, WithStartRow, WithMultipleSheets
{
    public function __construct($param)
    {
        $this->param = $param;
    }

    public function model(array $row)
    {
        dd($row);
        if(!empty($row[11]) && !empty($row[12])){
            $chk = StockinImportDB::where([['warehouse_stockin_no',$row[5]],['sku',$row[7]],['stockin_time',$row[12]]])->first();
            if (empty($chk)) { //檢查是否不存在避免重複匯入
                return new StockinImportDB([
                    'import_no' => $this->param['import_no'],
                    'warehouse_stockin_no' => $row[5],
                    'sku' => $row[7],
                    'product_name' => $row[8],
                    'expected_quantity' => ceil($row[10]),
                    'stockin_quantity' => !empty($row[11]) ? ceil($row[11]) : 0,
                    'stockin_time' => !empty($row[12]) ? $row[12] : null,
                    'purchase_nos' => null,
                ]);
            }
        }
    }

    /** 從第2行開始
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    /*
     * 只取第一個sheets
     */
    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }
}
