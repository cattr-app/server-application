<?php

namespace App\Docs\OpenApi;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\RouteInfo;
use App\Http\Requests\CattrFormRequest;
use Dedoc\Scramble\Support\Type\Literal\LiteralBooleanType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\TemplateType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Str;

/**
 * Small per-operation corrections that Scramble cannot infer from the code.
 */
class OperationFixes extends OperationExtension
{
    /** Path parameters that are numeric ids but have no typed binding in the action signature. */
    private const INTEGER_PATH_PARAMETERS = ['interval'];

    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        // Scramble reads the group middleware and ignores withoutMiddleware(), so public routes get a 401.
        $excluded = $routeInfo->route->excludedMiddleware();
        if (collect($excluded)->contains(fn ($m) => is_string($m) && Str::startsWith($m, 'auth'))) {
            $operation->responses = array_values(array_filter(
                $operation->responses ?? [],
                static fn ($response) => !($response instanceof Reference && ltrim((string) $response->fullName, '\\') === AuthenticationException::class)
                    && (int) ($response->code ?? 0) !== 401,
            ));
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

        foreach ($operation->parameters as $parameter) {
            if ($parameter instanceof Parameter
                && $parameter->in === 'path'
                && in_array($parameter->name, self::INTEGER_PATH_PARAMETERS, true)
            ) {
                $parameter->schema = Schema::fromType(new IntegerType);
            }
        }
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
