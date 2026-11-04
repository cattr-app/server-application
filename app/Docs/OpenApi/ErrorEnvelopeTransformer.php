<?php

namespace App\Docs\OpenApi;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

/**
 * Replaces the Laravel default error bodies (`{"message": ...}`) with the envelope that
 * App\Exceptions\Handler and App\Http\Responses\CattrErrorResponse actually produce:
 * `{"status": 422, "success": false, "error": {"code": "validation_failed", "message": ..., "fields": {...}}}`.
 */
class ErrorEnvelopeTransformer implements DocumentTransformer
{
    /** Response attribute: the description was set deliberately and is kept. */
    public const KEEP_DESCRIPTION = 'keepDescription';

    /** Default `error.code` and wording per status; see tests/TestResponse.php for the codes. */
    private const DESCRIPTIONS = [
        401 => 'Unauthenticated, `error.code` is `authorization.unauthorized`',
        403 => 'Forbidden, `error.code` is `authorization.forbidden`',
        404 => 'Not found, `error.code` is `query.item_not_found`',
        409 => 'Conflict, `error.code` is `query.item_already_exists`',
        422 => 'Validation failed, `error.code` is `validation_failed` and `error.fields` lists the messages per field',
    ];

    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $error = $document->components->addSchema('Error', Schema::fromType($this->errorType()));

        foreach ($document->components->responses as $response) {
            $this->rewrite($response, $error);
        }

        foreach ($document->paths as $path) {
            foreach ($path->operations as $operation) {
                foreach ($operation->responses ?? [] as $response) {
                    $this->rewrite($response, $error);
                }
            }
        }
    }

    private function rewrite(mixed $response, mixed $error): void
    {
        if (!$response instanceof Response || (int) $response->code < 400) {
            return;
        }

        $response->setContent('application/json', Schema::fromType($error));

        if (isset(self::DESCRIPTIONS[(int) $response->code]) && !$response->getAttribute(self::KEEP_DESCRIPTION)) {
            $response->setDescription(self::DESCRIPTIONS[(int) $response->code]);
        }
    }

    private function errorType(): ObjectType
    {
        $fields = (new ObjectType)
            ->setDescription('Validation messages per field (422 only).')
            ->additionalProperties((new \Dedoc\Scramble\Support\Generator\Types\ArrayType)->setItems(new StringType));

        $details = (new ObjectType)
            ->addProperty('code', (new StringType)->setDescription('Machine readable error code, e.g. `validation_failed`.'))
            ->addProperty('message', new StringType)
            ->addProperty('fields', $fields)
            ->addProperty('info', (new ObjectType)->setDescription('Extra data of some errors, e.g. the reCAPTCHA site key.'))
            ->addProperty('trace_id', (new StringType)->setDescription('Correlation id of a server error (5xx), also written to the log.'))
            ->setRequired(['code', 'message']);

        return (new ObjectType)
            ->addProperty('status', new IntegerType)
            ->addProperty('success', (new BooleanType)->example(false))
            ->addProperty('error', $details)
            ->setRequired(['status', 'success', 'error']);
    }
}
