<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

class EmptySheet implements FromCollection,WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data[0] = ['如果你看到這個表單，代表你的參數有錯誤，找不到正確的資料'];
        return collect($data);
    }
    public function title(): string
    {
        return '這是空表單';
    }
}
