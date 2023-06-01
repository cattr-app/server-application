<?php

namespace App\Exceptions;

use App\Exceptions\Entities\MethodNotAllowedException;
use Crypt;
use Filter;
use Flugg\Responder\Exceptions\ConvertsExceptions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use PDOException;
use Str;
use Symfony\Component\HttpFoundation\Response;
use Flugg\Responder\Exceptions\Http\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

/**
 * Class Handler
 */
class Handler extends ExceptionHandler
{
    use ConvertsExceptions;

    protected string $traceId;

    public function __construct(Container $container)
    {
        $this->traceId = Str::uuid()->toString();

        try {
            // Only add trace_id to error response if Filter::getErrorResponseFilterName() method exists
            $self = $this;
            Filter::listen(Filter::getErrorResponseFilterName(), static function (array|null $data = []) use ($self) {
                $data['trace_id'] = $self->traceId;
                return $data;
            });
        } catch (Throwable $exception) {
        }
        parent::__construct($container);
    }

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport
        = [
            AuthenticationException::class,
            AuthorizationException::class,
            Entities\AuthorizationException::class,
            HttpException::class,
            ModelNotFoundException::class,
            TokenMismatchException::class,
            ValidationException::class,
            PDOException::class,
        ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
    }

    /**
     * Get the default context variables for logging.
     *
     * @return array<string, mixed>
     */
    protected function context(): array
    {
        $requestContent = collect(rescue(fn() => json_decode(request()->getContent(), true), [], false))
            ->map(function ($item, string $key) {
                if (Str::contains($key, ['password', 'secret', 'token', 'api_key'], true)) {
                    return '***';
                }
                return $item;
            })->toJson();

        return array_merge(parent::context(), [
            'trace_id' => $this->traceId,
            'request_uri' => request()->getRequestUri(),
            'request_content' => config('app.debug') ? $requestContent : Crypt::encryptString($requestContent)
        ]);
    }

    public function render($request, $e): Response
    {
        $this->convert($e, [
            MethodNotAllowedHttpException::class => fn($e) => throw new MethodNotAllowedException($e->getMessage()),
            AuthenticationException::class => fn($e
            ) => throw new Entities\AuthorizationException(Entities\AuthorizationException::ERROR_TYPE_UNAUTHORIZED),
        ]);

        $this->convertDefaultException($e);

        if ($e instanceof HttpException) {
            return $this->renderResponse($e);
        }

        return responder()->error($e->getCode(), $e->getMessage())->respond();
    }
}
