<?php

namespace App\Docs\OpenApi;

use App\Helpers\ReportHelper;
use App\Http\Requests\CattrFormRequest;
use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Header;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType as OpenApiObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\Literal\LiteralBooleanType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\TemplateType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Small per-operation corrections that Scramble cannot infer from the code.
 */
class OperationFixes extends OperationExtension
{
    /** Path parameters that are numeric ids but have no typed binding in the action signature. */
    private const INTEGER_PATH_PARAMETERS = ['interval'];

    /**
     * Successful responses that are not the JSON envelope, by route name: media type, schema format, description.
     * A docblock cannot set the media type of a response.
     */
    private const FILE_RESPONSES = [
        'attachment.temporary-download' => [
            'application/octet-stream',
            'binary',
            'The attachment file; `Content-Type` is the MIME type of the attachment.',
            ['Content-Disposition' => 'Download of the file under the original name of the attachment.'],
        ],
        'offline_sync.download_projects_and_tasks' => [
            'application/octet-stream',
            'binary',
            'The MessagePack file `ProjectsAndTasks.cattr`.',
            ['Content-Disposition' => 'Download of the file `ProjectsAndTasks.cattr`.'],
        ],
        'attachment.temporary_url' => ['text/html', null, 'The URL as a bare string, without the JSON envelope.', []],
    ];

    /**
     * Errors that are thrown from helpers and exception classes Scramble cannot follow, by route name and status.
     * `error.code` is given for each of them: see {@see \App\Exceptions\Entities\AuthorizationException} and, for
     * `abort()`, the reason-phrase codes of {@see \App\Exceptions\Handler}.
     */
    private const ERRORS = [
        'auth.login' => [
            401 => 'Wrong email or password, `error.code` is `authorization.unauthorized`',
            403 => 'The user is deactivated, `error.code` is `authorization.user_disabled`',
            423 => 'Too many failed attempts, `error.code` is `authorization.banned`',
            429 => 'A reCAPTCHA token is required or invalid, `error.code` is `authorization.captcha` and `error.info.site_key` is the public site key',
        ],
        'auth.desktop.process' => [
            401 => 'The desktop key is missing, malformed, unknown or expired, `error.code` is `authorization.unauthorized`',
            403 => 'The user is deactivated, `error.code` is `authorization.user_disabled`',
        ],
        'auth.refresh' => [
            400 => 'The method is deprecated and always fails, `error.code` is `deprecation.api`',
        ],
        'auth.reset.request' => [
            400 => 'Invalid parameters, `error.code` is `authorization.wrong_params`',
            404 => 'No user with this email, `error.code` is `authorization.user_not_found`',
            429 => 'A reCAPTCHA token is required or invalid, `error.code` is `authorization.captcha`',
        ],
        'auth.reset.validate' => [
            400 => 'Invalid parameters, `error.code` is `authorization.wrong_params`',
            401 => 'The reset data is invalid, `error.code` is `authorization.invalid_password_data`',
        ],
        'auth.reset.process' => [
            400 => 'Invalid parameters, `error.code` is `authorization.wrong_params`',
            401 => 'The reset data is invalid, `error.code` is `authorization.invalid_password_data` or `authorization.unauthorized`',
        ],
        'intervals.screenshot.original' => [
            403 => 'The signature is missing, invalid or expired, `error.code` is `authorization.forbidden`',
            404 => 'The interval has no screenshot, `error.code` is `not_found`',
        ],
        'intervals.screenshot.thumb' => [
            403 => 'The signature is missing, invalid or expired, `error.code` is `authorization.forbidden`',
            404 => 'The interval has no thumbnail, `error.code` is `not_found`',
        ],
        'intervals.screenshot.put' => [
            409 => 'The interval already has a screenshot or screenshots are not allowed for it, `error.code` is `conflict`',
        ],
        'offline_sync.upload_intervals' => [
            400 => 'The archive cannot be opened or read, its digest does not match, it has no intervals or the user is unknown, `error.code` is `bad_request`',
        ],
        'offline_sync.upload_screenshots' => [
            400 => 'The archive cannot be read or contains a file that is not named `<user_id>_<screenshot_id>`, `error.code` is `bad_request`',
        ],
        'attachment.temporary-download' => [
            403 => 'The signature is missing, invalid or expired, `error.code` is `authorization.forbidden`',
        ],
    ];

    /** Routes that only fail, so the success response inferred for them does not exist. */
    private const ALWAYS_FAILING = ['auth.refresh'];

    /** Report downloads: the format is taken from the `Accept` header of the request. */
    private const REPORT_DOWNLOADS = [
        'report.project.download',
        'report.dashboard.download',
        'report.planned-time.download',
        'report.universal-report.download',
    ];

    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        // Scramble reads the group middleware and ignores withoutMiddleware(), so public routes get a 401 and bearer security.
        $excluded = $routeInfo->route->excludedMiddleware();
        if (collect($excluded)->contains(fn ($m) => is_string($m) && Str::startsWith($m, 'auth'))) {
            $operation->responses = array_values(array_filter(
                $operation->responses ?? [],
                static fn ($response) => !($response instanceof Reference && ltrim((string) $response->fullName, '\\') === AuthenticationException::class)
                    && (int) ($response->code ?? 0) !== 401,
            ));
            // Same for the security requirement: the route is public.
            $operation->security = [];
        }

        // CattrFormRequest::authorize() passes through a filter, so Scramble sees a non-literal result
        // even where _authorize() simply returns true.
        if ($this->authorizesEveryone($routeInfo)) {
            $operation->responses = array_values(array_filter(
                $operation->responses ?? [],
                static fn ($response) => !($response instanceof Reference && ltrim((string) $response->fullName, '\\') === AuthorizationException::class)
                    && (int) ($response->code ?? 0) !== 403,
            ));
        }

        $name = $routeInfo->route->getName();

        if ($name !== null) {
            $this->applyFileResponse($operation, $name);
            $this->applyErrors($operation, $name);
        }

        if (in_array($name, self::REPORT_DOWNLOADS, true)) {
            $this->describeAcceptHeader($operation);
        }

        foreach ($operation->parameters as $parameter) {
            if ($parameter instanceof Parameter
                && $parameter->in === 'path'
                && in_array($parameter->name, self::INTEGER_PATH_PARAMETERS, true)
            ) {
                $parameter->schema = Schema::fromType(new IntegerType);
            }
        }
    }

    private function applyFileResponse(Operation $operation, string $name): void
    {
        if (!isset(self::FILE_RESPONSES[$name])) {
            return;
        }

        [$mediaType, $format, $description, $headers] = self::FILE_RESPONSES[$name];

        foreach ($operation->responses ?? [] as $response) {
            if (!$response instanceof Response || (int) $response->code !== 200) {
                continue;
            }

            // Replaces the inferred JSON/HTML content, so that no wrong media type stays beside the right one.
            $response->content = [];
            $response->setContent($mediaType, Schema::fromType($format ? (new StringType)->format($format) : new StringType));
            $response->setDescription($description);
            foreach ($headers as $header => $headerDescription) {
                $response->headers[$header] = new Header($headerDescription, schema: Schema::fromType(new StringType));
            }
        }
    }

    private function applyErrors(Operation $operation, string $name): void
    {
        if (in_array($name, self::ALWAYS_FAILING, true)) {
            $operation->responses = array_values(array_filter(
                $operation->responses ?? [],
                static fn ($response) => !($response instanceof Response && (int) $response->code < 400),
            ));
        }

        foreach (self::ERRORS[$name] ?? [] as $status => $description) {
            $operation->responses = array_values(array_filter(
                $operation->responses ?? [],
                fn ($response) => $this->statusOf($response) !== $status,
            ));

            $response = Response::make($status)
                ->setDescription($description)
                ->setContent('application/json', Schema::fromType(new OpenApiObjectType));
            // ErrorEnvelopeTransformer must not replace this description with the generic one.
            $response->setAttribute(ErrorEnvelopeTransformer::KEEP_DESCRIPTION, true);

            $operation->addResponse($response);
        }
    }

    private function statusOf(mixed $response): ?int
    {
        if ($response instanceof Response) {
            return (int) $response->code;
        }

        if ($response instanceof Reference) {
            return match (ltrim((string) $response->fullName, '\\')) {
                AuthenticationException::class => 401,
                AuthorizationException::class => 403,
                ModelNotFoundException::class => 404,
                ValidationException::class => 422,
                default => null,
            };
        }

        return null;
    }

    private function describeAcceptHeader(Operation $operation): void
    {
        $formats = collect(ReportHelper::getAvailableReportFormats())
            ->map(static fn (string $mime, string $extension) => "`$mime` ($extension)")
            ->implode(', ');

        $operation->description = trim($operation->description
            . "\n\nThe file format is chosen by the `Accept` header of the request: $formats. Any other value gives 422.");
    }

    private function authorizesEveryone(RouteInfo $routeInfo): bool
    {
        $request = collect($routeInfo->getActionType()?->arguments ?? [])
            ->map(fn (Type $argument): ?Type => $argument instanceof TemplateType ? $argument->is : $argument)
            ->first(fn (?Type $argument) => $argument instanceof ObjectType && $argument->isInstanceOf(CattrFormRequest::class));

        if (!$request) {
            return false;
        }

        $definition = $this->infer->analyzeClass($request->name);

        // AuthorizesAfterValidation keeps _authorize() true and checks the rights in authorizeValidated().
        if ($definition->hasMethodDefinition('authorizeValidated')) {
            return false;
        }

        $authorized = $definition->getMethodCallType('_authorize');

        return $authorized instanceof LiteralBooleanType && $authorized->value === true;
    }
}
