<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // noop for now
        });
    }

    /**
     * Return a JSON response for validation exceptions.
     */
    protected function invalidJson($request, ValidationException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage() ?: 'The given data was invalid.',
            'errors' => $exception->errors(),
        ], $exception->status);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            if ($e instanceof ValidationException) {
                return $this->invalidJson($request, $e);
            }

            $status = 500;
            if (method_exists($e, 'getStatusCode')) {
                $status = $e->getStatusCode();
            }

            $message = $e->getMessage() ?: ($status === 500 ? 'Server Error' : 'Error');

            return response()->json([
                'message' => $message,
            ], $status);
        }

        return parent::render($request, $e);
    }
}
