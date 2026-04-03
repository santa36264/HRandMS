<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [];

    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {});
    }

    /**
     * Render all exceptions as a consistent JSON envelope for API routes.
     *
     * Shape:
     *   { "success": false, "message": "...", "errors": {...} }
     */
    public function render($request, Throwable $e)
    {
        // Only intercept API / JSON requests
        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return parent::render($request, $e);
        }

        // ── Validation ────────────────────────────────────────────────────
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        }

        // ── Unauthenticated ───────────────────────────────────────────────
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in.',
                'errors'  => [],
            ], 401);
        }

        // ── Forbidden ─────────────────────────────────────────────────────
        if ($e instanceof AccessDeniedHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
                'errors'  => [],
            ], 403);
        }

        // ── Model not found / route not found ─────────────────────────────
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            $model   = $e instanceof ModelNotFoundException
                ? class_basename($e->getModel())
                : null;
            $message = $model ? "{$model} not found." : 'The requested resource was not found.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors'  => [],
            ], 404);
        }

        return parent::render($request, $e);
    }
}
