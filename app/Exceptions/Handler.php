<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    protected $dontReport = [];

    /**
     * A list of the internal exception types that should not be reported.
     *
     * @var array
     */
    protected $internalDontReport = [];

    /**
     * @param Exception $exception
     * @return mixed|void
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        if (!config('app.debug') && app()->bound('sentry') && $this->shouldReport($exception)){
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @param Exception $exception
     * @return Response|JsonResponse
     */
    public function render($request, Exception $exception)
    {
        if (!config('app.json_errors')) {
            return parent::render($request, $exception);
        }

        $statusCode = 500;
        $message = $exception->getMessage();
        $class = get_class($exception);
        $reason = null;

        if ($exception instanceof ModelNotFoundException) {
            $statusCode = 404;
        } elseif ($exception instanceof NotFoundHttpException) {
            $message = sprintf(
                'Unknown uri: %s',
                $request->getRequestUri()
            );
        } elseif ($exception instanceOf MethodNotAllowedHttpException) {
            $message = sprintf(
                'Method "%s" is not allowed for uri %s',
                $request->getMethod(),
                $request->getRequestUri()
            );
        } elseif ($exception instanceof Entities\AuthorizationException) {
            $reason = $exception->getReason();
        }

        $data = [
            'status' => false,
            'status_code' => $statusCode,
            'error' => $message,
            'type' => preg_replace('#^.*\\\\#', '', $class),
        ];

        if ($reason !== null) {
            $data['reason'] = $reason;
        }

        if (config('app.debug')) {
            $data['class'] = $class;
            $data['error_code'] = $exception->getCode();
            $data['trace'] = explode("\n", $exception->getTraceAsString());
        }

        return response()->json($data, $statusCode);
    }
}
