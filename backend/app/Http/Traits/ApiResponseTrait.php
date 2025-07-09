<?php

namespace App\Http\Traits;

trait ApiResponseTrait
{
    /**
     * Success response format
     */
    protected function successResponse($data = null, $message = null, $statusCode = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json(array_filter($response), $statusCode);
    }

    /**
     * Error response format
     */
    protected function errorResponse($message = null, $statusCode = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ];

        return response()->json(array_filter($response), $statusCode);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse($errors, $message = 'Validation failed')
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorizedResponse($message = 'Unauthorized')
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Forbidden response
     */
    protected function forbiddenResponse($message = 'Forbidden')
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Not found response
     */
    protected function notFoundResponse($message = 'Resource not found')
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Internal server error response
     */
    protected function serverErrorResponse($message = 'Internal server error')
    {
        return $this->errorResponse($message, 500);
    }
}