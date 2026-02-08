<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

trait ApiResponseTrait
{
    /* -----------------------------------------------------------------
    |  SUCCESS RESPONSES
    | -----------------------------------------------------------------
    */

    protected function success(
        mixed $data = null,
        string $message = 'Succès',
        int $status = Response::HTTP_OK
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function created(
        mixed $data = null,
        string $message = 'Ressource créée avec succès'
    ): JsonResponse {
        return $this->success($data, $message, Response::HTTP_CREATED);
    }

    protected function noContent(
        string $message = 'Aucun contenu'
    ): JsonResponse {
        return $this->success(null, $message, Response::HTTP_NO_CONTENT);
    }

    /* -----------------------------------------------------------------
     |  PAGINATION
     | -----------------------------------------------------------------
     */

    protected function paginated(
        $paginator,
        string $message = 'Succès'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'errors' => null,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /* -----------------------------------------------------------------
     |  ERROR RESPONSES
     | -----------------------------------------------------------------
     */

    protected function error(
        string $message = 'Une erreur est survenue',
        mixed $errors = null,
        int $status = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    protected function forbidden(
        string $message = 'Accès interdit'
    ): JsonResponse {
        return $this->error($message, null, Response::HTTP_FORBIDDEN);
    }

    protected function notFound(
        string $message = 'Ressource non trouvée'
    ): JsonResponse {
        return $this->error($message, null, Response::HTTP_NOT_FOUND);
    }

    protected function unauthorized(
        string $message = 'Non authentifié'
    ): JsonResponse {
        return $this->error($message, null, Response::HTTP_UNAUTHORIZED);
    }

    protected function conflict(
        string $message = 'Conflit de données'
    ): JsonResponse {
        return $this->error($message, null, Response::HTTP_CONFLICT);
    }

    /* -----------------------------------------------------------------
     |  VALIDATION ERRORS
     | -----------------------------------------------------------------
     */

    protected function validationError(
        ValidationException $exception
    ): JsonResponse {
        return $this->error(
            'Erreur de validation',
            $exception->errors(),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /* -----------------------------------------------------------------
     |  EXCEPTION HANDLING
     | -----------------------------------------------------------------
     */

    protected function handleException(Throwable $exception): JsonResponse
    {
        // Validation
        if ($exception instanceof ValidationException) {
            return $this->validationError($exception);
        }

        // HTTP Exceptions (404, 403, etc.)
        if ($exception instanceof HttpExceptionInterface) {
            return $this->error(
                $exception->getMessage() ?: Response::$statusTexts[$exception->getStatusCode()],
                null,
                $exception->getStatusCode()
            );
        }

        // Exception générique (500)
        return $this->error(
            config('app.debug') ? $exception->getMessage() : 'Erreur interne du serveur',
            config('app.debug') ? [
                'exception' => class_basename($exception),
                'trace' => collect($exception->getTrace())->take(5),
            ] : null,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
