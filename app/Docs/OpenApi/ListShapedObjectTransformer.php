<?php

namespace App\Docs\OpenApi;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\Type;

/**
 * Turns an object whose properties are named `0`, `1`, ... into a tuple.
 *
 * Rules like `orderBy.0` and `orderBy.1` (see App\Helpers\QueryHelper) make Scramble describe `orderBy` as an object
 * with numeric keys. PHP stores those keys as a list, so the exported `properties` is a JSON array and the document
 * is not valid OpenAPI. The validation means `orderBy: [field, direction]`, so it is documented as an array.
 */
class ListShapedObjectTransformer implements DocumentTransformer
{
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        foreach ($document->components->schemas as $schema) {
            $schema->type = $this->convert($schema->type);
        }
    }

    private function convert(Type $type): Type
    {
        if ($type instanceof ArrayType) {
            $type->items = $this->convert($type->items);
            $type->prefixItems = array_map($this->convert(...), $type->prefixItems);

            return $type;
        }

        if (!$type instanceof ObjectType) {
            return $type;
        }

        foreach ($type->properties as $name => $property) {
            if ($property instanceof Type) {
                $type->properties[$name] = $this->convert($property);
            }
        }

        if (!$this->isList($type)) {
            return $type;
        }

        $tuple = (new ArrayType)
            ->setPrefixItems(array_values($type->properties))
            // The first element is always required (`required_with` the array itself); the rest are optional.
            ->setMin(1)
            ->setMax(count($type->properties));
        $tuple->setDescription($type->description);

        return $tuple;
    }

    private function isList(ObjectType $type): bool
    {
        $keys = array_keys($type->properties);

        return $keys !== [] && $keys === range(0, count($keys) - 1) && !$type->additionalProperties;
    }
}
