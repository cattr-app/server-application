<?php

namespace App\Logging;

use DateTimeZone;
use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

final class CattrJsonFormatter extends JsonFormatter
{
    public function format(LogRecord $record): string
    {
        $normalized = json_decode(
            parent::format($record),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return json_encode([
            'time' => $record->datetime
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s.v\Z'),
            'level' => strtolower($record->level->name),
            'service' => getenv('CATTR_LOG_SERVICE') ?: 'app',
            'logger' => $record->channel,
            'msg' => $record->message,
            'context' => $normalized['context'] ?? [],
            'extra' => $normalized['extra'] ?? [],
        ], JSON_UNESCAPED_UNICODE
           | JSON_UNESCAPED_SLASHES
           | JSON_PARTIAL_OUTPUT_ON_ERROR) . "\n";
    }
}
