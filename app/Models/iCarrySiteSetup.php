<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarrySiteSetup extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'site_setup';
    public $timestamps = FALSE;
}
