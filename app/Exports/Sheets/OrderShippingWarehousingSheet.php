<?php

namespace App\Exports\Sheets;

use App\Models\UserAddress as UserAddressDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\CompanySetting as CompanySettingDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ProductPackage as ProductPackageDB;
use App\Models\Country as CountryDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderShippingWarehousingSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize
{
    use OrderFunctionTrait;
    protected $param;

    public function __construct(array $param)
    {
        $this->param = $param;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data = [];

        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        $orderIds = $this->getOrderData($this->param);
        if(!empty($orderIds)){
            $items = OrderItemDB::join($orderTable,$orderTable.'.id',$orderItemTable.'.order_id')
                ->join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
                ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                ->whereIn($orderTable.'.id',$orderIds)
                ->where($orderItemTable.'.is_del',0)
                ->select([
                    $orderItemTable.'.*',
                    $orderItemTable.'.product_model_id',
                    $productModelTable.'.sku',
                    $productModelTable.'.gtin13',
                    $productTable.'.id as product_id',
                    DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                    $vendorTable.'.name as vendor_name',
                    $productTable.'.serving_size',
                    $productTable.'.package_data',
                    $orderTable.'.book_shipping_date',
                    // DB::raw("GROUP_CONCAT(orders.book_shipping_date) as shippingDate"), //驗證測試用
                    DB::raw("SUM((CASE WHEN $orderItemTable.is_call is null THEN $orderItemTable.quantity ELSE 0 END)) as quantity"),
                ])->groupBy('sku','book_shipping_date')->orderBy('sku','asc')->get();

            $c = 1;
            foreach ($items as $item) {
                if($item->quantity > 0 && $item->is_del == 0){
                    if(strstr($item->sku,'BOM')){ //組合商品需要另外抓單品使用數量
                        if(!empty($item->package_data)){
                            $packageData = json_decode(str_replace('	','',$item->package_data));
                            if(is_array($packageData)){
                                foreach($packageData as $package){
                                    if($item->sku == $package->bom){
                                        foreach($package->lists as $list){
                                            $quantity = $item->quantity * $list->quantity;
                                            $product = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                            ->where($productModelTable.'.sku',$list->sku)
                                            ->select([
                                                $productModelTable.'.*',
                                                $vendorTable.'.name as vendor_name',
                                                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                                $productTable.'.serving_size',
                                            ])->first();
                                            if(!empty($product)){
                                                $data[] = [
                                                    '',
                                                    $c,
                                                    $product->sku,
                                                    $product->gtin13 ?? $product->sku,
                                                    $product->vendor_name,
                                                    $product->product_name,
                                                    $product->serving_size,
                                                    $quantity,
                                                    '',
                                                    '預計日期: '.$item->book_shipping_date,
                                                ];
                                                $c++;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }else{
                        $data[] = [
                            '',
                            $c,
                            $item->sku,
                            $item->gtin13 ?? $item->sku,
                            $item->vendor_name,
                            $item->product_name,
                            $item->serving_size,
                            $item->quantity,
                            '',
                            '預計日期: '.$item->book_shipping_date,
                        ];
                        $c++;
                    }
                }
            }
        }
        $this->count = $c;
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $count = $this->count + 2;
        $sheet->mergeCells('A1:I1'); //合併第一行A-I
        $sheet->getStyle('A1:I1')->getFont()->setSize(20)->setBold(true); //第一行字型大小
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        for($i=1; $i<$count; $i++){
            $sheet->getStyle("A$i:J$i")->getBorders()->getAllBorders() //框線
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }
        $sheet->getStyle('D')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('D')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '庫存管理表(合併數量)';
    }
    public function columnWidths(): array
    {
        $highestColumn = 'Z';
        $highestColumn++;
        for ($column = 'A'; $column !== $highestColumn; $column++) {
            $width[$column] = 20;
        }
        return $width;
    }
    public function headings(): array
    {
        return [
            [
                '【直流電通】  庫存管理表(合併數量)',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '日期: '.date('Y/m/d'),
            ],
            [
                '叫貨日期',
                '序號',
                '商品條碼(貨號)',
                '參考號(國際條碼)',
                '廠商',
                '商品名稱',
                '商品規格',
                '預收數量',
                '有效日期',
                '備註',
            ]
        ];
    }
}

