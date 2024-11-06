<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryLanguagePack extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'language_packs';
    protected $fillable = [
        'key_value',
        'tw',
        'en',
        'jp',
        'kr',
        'th',
        'memo',
    ];
}
