<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a success JSON response.
     */
    protected function successResponse(mixed $data, string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        // Jika data berbentuk array dan memiliki struktur paginasi bawaan resource
        if (is_array($data) && array_key_exists('data', $data) && array_key_exists('meta', $data)) {
            $response['data'] = $data['data'];
            
            if (array_key_exists('links', $data)) {
                $response['links'] = $data['links'];
            }
            
            $response['meta'] = $data['meta'];
        } else {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message, int $code = 400, mixed $data = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }
}
