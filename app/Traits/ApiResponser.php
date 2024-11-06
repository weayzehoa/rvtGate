<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponser
{
    protected function dataResponse($data, $model = null, $id = null, $code = 200)
    {
        return response()->json([
            'status'=> 'Success',
            'model'=> $model,
            'id' => $id,
            'data' => $data
        ], $code)->header('Authorization',request()->header('authorization'));
    }

    protected function successResponse($total, $data, $message = null, $code = 200)
    {
        return response()->json([
            'status'=> 'Success',
            'message' => $message,
            'total' => $total,
            'data' => $data
        ], $code)->header('Authorization',request()->header('authorization'));
    }

    protected function errorResponse($message, $code)
    {
        return response()->json([
            'status'=>'Error',
            'message' => $message,
        ], $code)->header('Authorization',request()->header('authorization'));
    }

    protected function appCodeResponse($status, $appCode, $message, $httpCode)
    {
        return response()->json([
            'status'=> $status,
            'appCode' => $appCode,
            'message' => $message,
            'httpCode' => $httpCode,
        ], $httpCode)->header('Authorization',request()->header('authorization'));
    }

    protected function appDataResponse($status, $appCode, $message, $data, $httpCode = 200)
    {
        return response()->json([
            'status'=> $status,
            'appCode' => $appCode,
            'message' => $message,
            'data' => $data,
            'httpCode' => $httpCode,
        ], $httpCode)->header('Authorization',request()->header('authorization'));
    }

    protected function debugResponse($message, $httpCode = 200)
    {
        return response()->json([
            'status'=> 'debug',
            'message' => $message,
        ], $httpCode)->header('Authorization',request()->header('authorization'));
    }
}
