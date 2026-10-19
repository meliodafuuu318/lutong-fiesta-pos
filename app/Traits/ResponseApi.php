<?php

namespace App\Traits;

trait ResponseApi
{
    protected function success($message, $data = [], $statusCode = 200, $meta = []){

        return response()->json(array_merge([
            "message" => $message,
            "data" => $data,
            "code" => $statusCode,
            "error" => false
        ], $meta), $statusCode);
    }


    protected function error($message, $statusCode = 500, $data = [], $error = true){

        return response()->json([
            "message" => $message,
            "data" => $data,
            "code" => $statusCode,
            "error" => $error
        ], $statusCode);
    }
}
