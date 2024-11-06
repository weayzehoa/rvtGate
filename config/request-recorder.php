<?php
/**
 * littleBookBoy/laravel-request-recorder Config
 */
return [
    /**
     * - enabled : true or false
     * - group : route middleware group name
     * - except : 僅記錄除了這些方法之外的請求, 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'
     * - skip_routes : 僅記錄除了這些路由之外的請求, 也可限定只排除該路由的某些 rsponse http code
     */
    'recorder' => [
        'enabled' => env('REQUEST_RECORDER_ENABLED', false),
        'group' => env('REQUEST_RECORDER_GROUP', 'api'),
        'except' => [''],
        'skip_routes' => [
            [ 'route_name' => 'uuid.index', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.curation.index', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.curation.show', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.user.show', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.receiverBase.index', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.shippingMethod.index', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.category.index', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.country.index', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.vendor.index', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.vendor.show', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.product.index', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.product.show', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.product.availableDate', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.product.allowCountry', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.shoppingCart.index', 'http_code' => ['*'] ],
            [ 'route_name' => 'webapi.v1.shoppingCart.amount', 'http_code' => ['*'] ],
        ]
    ]
];
