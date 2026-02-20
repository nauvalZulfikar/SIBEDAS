<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait GlobalApiResponse
{
    protected function resSuccess($result, $code = 200){
        
        $response = [
            'success' => true,
            'data' => $result
        ];
        
        return response()->json($response, $code);
    }
    protected function resError(string $message, $errors = null, int $code = 400){
        Log::error($message);
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
