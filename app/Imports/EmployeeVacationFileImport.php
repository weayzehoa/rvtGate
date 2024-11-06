<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class EmployeeVacationFileImport implements ToCollection,WithStartRow,WithMultipleSheets,WithLimit,WithColumnLimit,WithColumnFormatting
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
    }
    /** 從第2行開始
     * @return int
     */
    public function startRow(): int
    {
        return 1;
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

    //限制讀取數量
    public function limit(): int
    {
        return 10000;
    }

    //限制讀取欄位
    public function endColumn(): string
    {
        //A到D欄, 4欄
        return 'T';
    }

    //轉換格式為文字
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
