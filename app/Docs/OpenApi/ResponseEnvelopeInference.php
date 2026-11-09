<?php

namespace App\Docs\OpenApi;

use App\Http\Controllers\Api\ItemController;
use Dedoc\Scramble\Infer\Extensions\Event\FunctionCallEvent;
use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\FunctionReturnTypeExtension;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\BooleanType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\IntegerType;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Literal\LiteralBooleanType;
use Dedoc\Scramble\Support\Type\Literal\LiteralIntegerType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;
use Dedoc\Scramble\Support\Type\UnknownType;
use Flugg\Responder\Contracts\Responder;
use Flugg\Responder\Http\Responses\SuccessResponseBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

/**
 * Teaches Scramble the success envelope produced by flugger/laravel-responder with the Cattr serializer:
 * `{"status": 200, "success": true, "data": ..., "pagination": ...}`.
 *
 * Covers `responder()->success($data)->respond($status)` and the CRUD helpers of {@see ItemController}
 * (`_index`, `_show`, `_create`, `_edit`, `_count`, `_destroy`), whose model is taken from the MODEL constant.
 * Documentation only: used by `scramble:export`, never by the running application.
 */
class ResponseEnvelopeInference implements FunctionReturnTypeExtension, MethodReturnTypeExtension
{
    public function shouldHandle(ObjectType|string $callee): bool
    {
        if (is_string($callee)) {
            return $callee === 'responder';
        }

        return $callee->isInstanceOf(Responder::class)
            || $callee->isInstanceOf(SuccessResponseBuilder::class)
            || $callee->isInstanceOf(ItemController::class);
    }

    public function getFunctionReturnType(FunctionCallEvent $event): ?Type
    {
        return new ObjectType(Responder::class);
    }

    public function getMethodReturnType(MethodCallEvent $event): ?Type
    {
        $instance = $event->getInstance();

        if ($instance->isInstanceOf(ItemController::class)) {
            return $this->itemControllerType($instance->name, $event->name);
        }

        if ($instance->isInstanceOf(Responder::class) && $event->name === 'success') {
            // A custom transformer reshapes the data, so its type is not known.
            $data = $event->arguments->get('transformer', 1, null) ? new UnknownType : $event->getArg('data', 0, new NullType);

            return new Generic(SuccessResponseBuilder::class, [$data]);
        }

        if ($instance instanceof Generic && $instance->isInstanceOf(SuccessResponseBuilder::class) && $event->name === 'respond') {
            $status = $event->getArg('status', 0, new LiteralIntegerType(200));

            return $this->envelope($instance->templateTypes[0] ?? new NullType, $status);
        }

        // Fluent builder methods (meta(), serializeWith(), ...) keep the data type.
        if ($instance instanceof Generic && $instance->isInstanceOf(SuccessResponseBuilder::class)) {
            return $instance;
        }

        return null;
    }

    private function itemControllerType(string $controller, string $method): ?Type
    {
        /** @var class-string<Model> $model */
        $model = (new \ReflectionClass($controller))->getConstant('MODEL') ?: Model::class;
        $item = $model === Model::class ? new ArrayType : new ObjectType($model);

        return match ($method) {
            '_index' => $this->envelope(new ArrayType(value: $item), new LiteralIntegerType(200), withPagination: true),
            '_show', '_create', '_edit' => $this->envelope($item, new LiteralIntegerType(200)),
            '_count' => $this->envelope(
                new KeyedArrayType([new ArrayItemType_('total', new IntegerType)]),
                new LiteralIntegerType(200),
            ),
            '_destroy' => $this->envelope(new NullType, new LiteralIntegerType(204)),
            default => null,
        };
    }

    private function envelope(Type $data, Type $status, bool $withPagination = false): Type
    {
        // 204 is sent without a body.
        if ($status instanceof LiteralIntegerType && $status->value === 204) {
            return new Generic(JsonResponse::class, [new NullType, $status, new ArrayType]);
        }

        $items = [
            new ArrayItemType_('status', new IntegerType),
            new ArrayItemType_('success', new LiteralBooleanType(true)),
            new ArrayItemType_('data', $data),
        ];

        if ($withPagination) {
            $items[] = new ArrayItemType_('pagination', new KeyedArrayType([
                new ArrayItemType_('count', new IntegerType),
                new ArrayItemType_('total', new IntegerType),
                new ArrayItemType_('perPage', new IntegerType),
                new ArrayItemType_('currentPage', new IntegerType),
                new ArrayItemType_('totalPages', new IntegerType),
                // Previous/next page URLs by name, an empty list when there is only one page.
                new ArrayItemType_('links', new Union([
                    new ArrayType(value: new StringType, key: new StringType),
                    new ArrayType,
                ])),
            ]), isOptional: true);
        }

        return new Generic(JsonResponse::class, [new KeyedArrayType($items), $status, new ArrayType]);
    }
}
