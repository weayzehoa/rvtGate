<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'ticketNotify',
        'openTicket',
        'acOrder',
        'nidin/Product',
        'nidin/Pay',
        'nidin/Order',
        'nidin/Return',
        'nidin/WriteOff',
        'nidin/OpenTicket',
        'nidin/Invalid',
        'nidin/Query',
        'nidin/Payment/Pay',
        'nidin/Payment/Query',
        'nidin/Payment/Capture',
        'nidin/Payment/Refund',
        'nidin/Payment/Notify'
    ];
}
