<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryProduct extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'product';
    //變更 Laravel 預設 created_at 與 updated_at 欄位名稱
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    protected $fillable = [
        'vendor_id',
        'category_id',
        'unit_name',
        'unit_name_id',
        'from_country_id',
        'product_sold_country',
        'name',
        'export_name_en',
        'brand',
        'serving_size',
        'shipping_methods',
        'price',
        'gross_weight',
        'net_weight',
        'title',
        'intro',
        'model_name',
        'model_type',
        'is_tax_free',
        'specification',
        'verification_reason',
        'status',
        'is_hot',
        'hotel_days',
        'airplane_days',
        'storage_life',
        'fake_price',
        'TMS_price',
        'allow_country',
        'allow_country_ids',
        'vendor_price',
        'unable_buy',
        'pause_reason',
        'tags',
        'is_del',
        'pass_time',
        'curation_text_top',
        'curation_text_bottom',
        'service_fee_percent',
        'package_data',
        'new_photo1',
        'new_photo2',
        'new_photo3',
        'new_photo4',
        'new_photo5',
        'type',
        'digiwin_product_category',
        'vendor_earliest_delivery_date',
        'vendor_latest_delivery_date',
        'shipping_fee_category_id', //棄用
        'ticket_price',
        'ticket_group',
        'ticket_merchant_no',
        'ticket_memo',
        'direct_shipment',
        'eng_name',
        'trans_start_date',
        'trans_end_date',
    ];
}
