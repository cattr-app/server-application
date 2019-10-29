<?php

namespace App\Exceptions;

use App\Exceptions\Entities\AuthorizationCaptchaException;
use App\Exceptions\Interfaces\ReasonableException;
use App\Exceptions\Interfaces\TypedException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class Handler
 * @package App\Exceptions
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        Entities\AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        TokenMismatchException::class,
        ValidationException::class,
    ];


    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  Exception $exception
     *
     * @return void
     * @throws Exception
     */
    public function report(Exception $exception): void
    {
        if (!config('app.debug') && app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request $request
     * @param  Exception $exception
     *
     * @return JsonResponse|Response
     */
    public function render($request, Exception $exception)
    {
        if (!config('app.json_errors')) {
            return parent::render($request, $exception);
        }

        $message = $exception->getMessage();
        $isHttpException = $this->isHttpException($exception);
        $cls = get_class($exception);
        $code = (int)$exception->getCode();

        $debugData = false;
        $reason = $exception instanceof ReasonableException ? $exception->getReason() : false;
        $errorType = $exception instanceof TypedException ? $exception->getType() : false;

        if (!$isHttpException) {
            $validExceptionsClasses = collect($this->dontReport)->filter(static function ($item) use ($exception) {
                return is_subclass_of($exception, $item);
            });

            if (!$validExceptionsClasses->count() && config('app.debug')) {
                $debugData = [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'code' => $code,
                    'class' => $cls,
                    'trace' => $exception->getTrace(),
                ];
            }
        }

        // Processing exception status code
        if ($exception instanceof \Error) {
            // If current exception is an PHP default error we'll interpret it as 500 Server Error code
            $statusCode = 500;
        } elseif ($isHttpException) {
            // Otherwise, if it is Laravel's HttpException we can access getStatusCode() method from exception
            // instance
            $statusCode = $exception->getStatusCode();

            if ($statusCode === 404) {
                $message = __('Requested url :url was not found', [
                    'url' => $request->getRequestUri(),
                ]);
                $errorType = 'http.request.not_found';
            } elseif ($statusCode === 405 && $message === '') {
                $message = __('Requested method :method is not allowed for :url', [
                    'method' => $request->getMethod(),
                    'url' => $request->getRequestUri(),
                ]);
                $errorType = 'http.request.wrong_method';
            }
        } elseif ($code === 404 || $code === 401 || $code === 429 || $code == 420) {
            // If we have 404 or 401 code we will process it as an request status code
            $statusCode = $code;
        } else {
            // Otherwise, if non of previous checks was correct we'll assuming that current exception was thrown
            // because of a bad request body
            $statusCode = 400;
        }

        if ($errorType === false) {
            // Get error type from exception class
            $errorType = strtolower(preg_replace(
                ['/^.*\\\\/', '/Exception$/', '/([^A-Z-])([A-Z])/'],
                ['', '', '$1_$2'],
                $cls
            ));
        }

        // Debug data will be passed to response body only if application currently in debug mode
        $exceptionResult = array_merge(
            [
                'error' => true,
                'message' => $message,
                'status_code' => $statusCode,
            ],
            $debugData !== false ? ['debug' => $debugData] : [],
            // Error Reason used for a more detailed explanation of the error on client side
            $reason !== false && $reason !== null ? ['reason' => $reason] : [],
            // Error Type used for a more accurate error processing on client side
            $errorType !== false && $errorType !== null ? ['type' => $errorType] : []
        );
        if ($exception instanceof AuthorizationCaptchaException) {
            $exceptionResult['site_key'] = AuthorizationCaptchaException::getSiteKey();
        }
        return response()->json(
            $exceptionResult,
            $statusCode,
            [],
            config('app.debug') ? JSON_PRETTY_PRINT : 0
        );
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return JsonResponse|Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $this->render($request, $exception);
    }
}
