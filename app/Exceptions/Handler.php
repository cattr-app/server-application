<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
     * @param  Exception  $exception
     *
     * @return void
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        if (!config('app.debug') && app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request    $request
     * @param  Exception  $exception
     *
     * @return Response
     */
    public function render($request, Exception $exception)
    {
        if (!config('app.json_errors')) {
            return parent::render($request, $exception);
        }

        $message = is_object($exception->getMessage()) ? $exception->getMessage()->toArray() : $exception->getMessage();

        if (config('app.debug')) {
            $validExceptionsClasses = array_map(function ($item) use ($exception) {
                return is_subclass_of($exception, $item);
            }, $this->dontReport);

            if (!$this->isHttpException($exception) && empty($validExceptionsClasses)) {
                $debugData = [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'code' => $exception->getCode(),
                    'class' => get_class($exception),
                    'trace' => $exception->getTrace()
                ];
            }
        }

        // Processing exception status code
        if ($exception instanceof \Error) {
            // If current exception is an PHP default error we'll interpret it as 500 Server Error code
            $statusCode = 500;
        } elseif ($this->isHttpException($exception)) {
            // Otherwise, if it is Laravel's HttpException we can access getStatusCode() method from exception
            // instance
            $statusCode = $exception->getStatusCode();
            if ($statusCode == 404) {
                $message = __("Requested url :url was not found", ['url' => $request->getRequestUri()]);
            }
        } elseif ($exception->getCode() == 404 || $exception->getCode() == 401) {
            // If we have 404 or 401 code we will process it as an request status code
            $statusCode = $exception->getCode();
        } else {
            // Otherwise, if non of previous checks was correct we'll assuming that current exception was thrown
            // because of a bad request body
            $statusCode = 400;
        }

        if ($exception instanceof Entities\AuthorizationException) {
            $authReason = $exception->getReason();
        }

        // Debug data will be passed to response body only if application currently in debug mode
        return response()->json(
            array_merge(
                ['message' => $message],
                isset($debugData) ? ['debug' => $debugData] : [],
                isset($authReason) ? ['reason' => $authReason] : []
            ),
            $statusCode
        );
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  Request                  $request
     * @param  AuthenticationException  $exception
     *
     * @return Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('/login');
    }
}
