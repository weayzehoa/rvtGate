<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class WarehouseShipImport implements ToCollection,WithStartRow,WithLimit,WithColumnLimit,WithColumnFormatting
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
    }

    /** 從第5行開始
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    //限制讀取行數量
    public function limit(): int
    {
        return 10000;
    }

    //限制讀取欄位
    public function endColumn(): string
    {
        //A到BO欄,67欄
        return 'BO';
    }

    //轉換格式為文字
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_TEXT,
            'J' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
            'L' => NumberFormat::FORMAT_TEXT,
            'M' => NumberFormat::FORMAT_TEXT,
            'N' => NumberFormat::FORMAT_TEXT,
            'P' => NumberFormat::FORMAT_TEXT,
            'O' => NumberFormat::FORMAT_TEXT,
            'Q' => NumberFormat::FORMAT_TEXT,
            'R' => NumberFormat::FORMAT_TEXT,
            'S' => NumberFormat::FORMAT_TEXT,
            'T' => NumberFormat::FORMAT_TEXT,
            'U' => NumberFormat::FORMAT_TEXT,
            'V' => NumberFormat::FORMAT_TEXT,
            'W' => NumberFormat::FORMAT_TEXT,
            'X' => NumberFormat::FORMAT_TEXT,
            'Y' => NumberFormat::FORMAT_TEXT,
            'Z' => NumberFormat::FORMAT_TEXT,
            'AA' => NumberFormat::FORMAT_TEXT,
            'AB' => NumberFormat::FORMAT_TEXT,
            'AC' => NumberFormat::FORMAT_TEXT,
            'AD' => NumberFormat::FORMAT_TEXT,
            'AE' => NumberFormat::FORMAT_TEXT,
            'AF' => NumberFormat::FORMAT_TEXT,
            'AG' => NumberFormat::FORMAT_TEXT,
            'AH' => NumberFormat::FORMAT_TEXT,
            'AI' => NumberFormat::FORMAT_TEXT,
            'AJ' => NumberFormat::FORMAT_TEXT,
            'AK' => NumberFormat::FORMAT_TEXT,
            'AL' => NumberFormat::FORMAT_TEXT,
            'AM' => NumberFormat::FORMAT_TEXT,
            'AN' => NumberFormat::FORMAT_TEXT,
            'AP' => NumberFormat::FORMAT_TEXT,
            'AO' => NumberFormat::FORMAT_TEXT,
            'AQ' => NumberFormat::FORMAT_TEXT,
            'AR' => NumberFormat::FORMAT_TEXT,
            'AS' => NumberFormat::FORMAT_TEXT,
            'AT' => NumberFormat::FORMAT_TEXT,
            'AU' => NumberFormat::FORMAT_TEXT,
            'AV' => NumberFormat::FORMAT_TEXT,
            'AW' => NumberFormat::FORMAT_TEXT,
            'AX' => NumberFormat::FORMAT_TEXT,
            'AY' => NumberFormat::FORMAT_TEXT,
            'AZ' => NumberFormat::FORMAT_TEXT,
            'BA' => NumberFormat::FORMAT_TEXT,
            'BB' => NumberFormat::FORMAT_TEXT,
            'BC' => NumberFormat::FORMAT_TEXT,
            'BD' => NumberFormat::FORMAT_TEXT,
            'BE' => NumberFormat::FORMAT_TEXT,
            'BF' => NumberFormat::FORMAT_TEXT,
            'BG' => NumberFormat::FORMAT_TEXT,
            'BH' => NumberFormat::FORMAT_TEXT,
            'BI' => NumberFormat::FORMAT_TEXT,
            'BJ' => NumberFormat::FORMAT_TEXT,
            'BK' => NumberFormat::FORMAT_TEXT,
            'BL' => NumberFormat::FORMAT_TEXT,
            'BM' => NumberFormat::FORMAT_TEXT,
            'BN' => NumberFormat::FORMAT_TEXT,
            'BP' => NumberFormat::FORMAT_TEXT,
            'BO' => NumberFormat::FORMAT_TEXT,
        ];
    }

}