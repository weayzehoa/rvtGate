<?php

namespace App\Exports\Sheets;

use App\Models\UserAddress as UserAddressDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\CompanySetting as CompanySettingDB;
use App\Models\ProductModel as ProductModelDB;
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

class OrderShippingWarehousingPreDeliverySheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize
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
        $orderIds = $this->getOrderData($this->param);
        if(count($orderIds) > 0){
            $items = OrderItemDB::with('package')->join('orders','orders.id','order_item.order_id')
                ->join('product_model','product_model.id','order_item.product_model_id')
                ->join('product','product.id','product_model.product_id')
                ->join('vendor','vendor.id','product.vendor_id')
                ->whereIn('order_id',$orderIds)
                ->select([
                    'order_item.*',
                    'product_model.sku',
                    'product_model.gtin13',
                    'product.id as product_id',
                    'vendor.name as vendor_name',
                    'product.serving_size',
                    'orders.book_shipping_date',
                    DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
                    DB::raw("(IF(orders.shipping_memo LIKE '%廠商發貨%','V','')) as vendor_send"),
                    // DB::raw("GROUP_CONCAT(orders.book_shipping_date) as shippingDate"), //驗證測試用
                    DB::raw("SUM((CASE WHEN order_item.is_call is null THEN order_item.quantity ELSE 0 END)) as quantity"),
                ])->groupBy('sku','book_shipping_date','vendor_send')
                ->orderBy('product_model.sku','asc')
                ->orderBy('vendor_send','asc')
                ->orderBy('orders.book_shipping_date','asc')
                ->get();
            $c = 1;
            if(count($items) > 0){
                foreach ($items as $item) {
                    if($item->quantity > 0){
                        if(strstr($item->sku,'BOM')){ //組合商品需要另外抓item資料
                            if(count($item->package) > 0){
                                foreach($item->package as $package){
                                    $quantity = $item->quantity * $package->quantity;
                                    $data[] = [
                                        $item->vendor_send,
                                        $c,
                                        $package->sku,
                                        $package->gtin13 ?? $package->sku,
                                        $package->vendor_name,
                                        $package->product_name,
                                        $package->serving_size,
                                        $quantity,
                                        '',
                                        '預計日期: '.$item->book_shipping_date,
                                    ];
                                    $c++;
                                }
                            }
                        }else{
                            $data[] = [
                                $item->vendor_send,
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
        return '庫存管理表(不合併數量)';
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
                '【直流電通】 商品入庫管理表',
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
                '廠商發貨',
                '序號',
                '商品條碼(貨號)',
                '參考號(國際條碼)',
                '廠商',
                '商品名稱',
                '商品規格',
                '預收數量',
                '有效日期',
                '備註(預定出貨日）',
            ]
        ];
    }
}

