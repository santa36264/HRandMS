<?php

namespace App\Traits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Ensures all FormRequest validation failures return a consistent
 * JSON envelope regardless of whether the request expects JSON.
 *
 * Response shape:
 * {
 *   "success": false,
 *   "message": "The given data was invalid.",
 *   "errors": { "field": ["message", ...] }
 * }
 */
trait ValidatesJson
{
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
