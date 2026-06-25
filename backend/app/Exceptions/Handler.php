<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

final class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    protected function shouldReturnJson($request, Throwable $e): bool
    {
        return $request->expectsJson() || $request->is('api/*');
    }

    protected function convertExceptionToArray(Throwable $e): array
    {
        if (config('app.debug')) {
            return parent::convertExceptionToArray($e);
        }

        return [
            'status'  => 'error',
            'message' => $this->getMessageForException($e),
        ];
    }

    private function getMessageForException(Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            return collect($e->errors())->flatten()->first() ?: $e->getMessage();
        }

        return match (true) {
            $e instanceof ModelNotFoundException => 'Data tidak ditemukan.',
            $e instanceof NotFoundHttpException => 'Halaman tidak ditemukan.',
            $e instanceof AuthenticationException => 'Silakan login terlebih dahulu.',
            $e instanceof AuthorizationException => 'Anda tidak memiliki akses.',
            $e instanceof TooManyRequestsHttpException,
            $e instanceof ThrottleRequestsException => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
            $e instanceof QueryException => 'Terjadi kesalahan pada server. Silakan coba lagi.',
            default => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan. Silakan coba lagi.',
        };
    }
}