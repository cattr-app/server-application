<?php

namespace App\Helpers;

use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

/**
 * An archive of offline screenshots exported by the desktop application: a flat list of files named
 * `<user_id>_<screenshot_id>` (the screenshot ID is a UUID).
 *
 * The request checks the archive before it is stored (validate() and scan()), the job extracts its entries
 * one by one (extractEntry()) with the same limit on the bytes actually written.
 */
class OfflineScreenshotsArchive
{
    private const EXTRACTION_CHUNK_SIZE = 64 * 1024;

    private function __construct(private readonly ZipArchive $zip)
    {
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 400 if the file is not a readable archive
     */
    public static function open(string $path): self
    {
        $zip = new ZipArchive();
        $result = $zip->open($path);
        abort_if(
            $result === false || (is_int($result) && $result > 0),
            400,
            __('Cannot open file.') . (is_int($result) ? " ZipArchive error code: $result" : ''),
        );

        return new self($zip);
    }

    public function close(): void
    {
        $this->zip->close();
    }

    public function count(): int
    {
        return $this->zip->count();
    }

    /**
     * Name of the entry, a plain file name once validate() has passed.
     */
    public function entryName(int $index): string
    {
        $name = $this->zip->getNameIndex($index);
        abort_if($name === false, 400, __('Cannot open file.'));

        return $name;
    }

    /**
     * Splits the entry name into the user ID and the screenshot ID, or returns null for any other name.
     *
     * @return array{int, string}|null
     */
    public static function parseEntryName(string $name): ?array
    {
        if (preg_match('/^(\d+)_(.+)\z/', $name, $matches) !== 1 || !Str::isUuid($matches[2])) {
            return null;
        }

        return [(int)$matches[1], $matches[2]];
    }

    /**
     * Rejects the archive before anything is extracted or stored if it cannot be processed (too many entries or too
     * large when unpacked), if an entry is not a plain file name (422) or not a screenshot file name (400).
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        $maxFiles = (int)config('app.offline_screenshots.max_files');
        $maxSize = (int)config('app.offline_screenshots.max_size');

        $fileCount = $this->zip->count();
        if ($fileCount > $maxFiles) {
            throw ValidationException::withMessages([
                'file' => __('validation.offline-sync.too_many_files', ['count' => $fileCount, 'max' => $maxFiles]),
            ]);
        }

        $totalSize = 0;
        $names = [];
        for ($index = 0; $index < $fileCount; $index++) {
            $name = $this->zip->getNameIndex($index);
            $stat = $this->zip->statIndex($index);

            // A repeated name would be processed twice
            if (!is_string($name) || $stat === false || !self::isPlainEntryName($name) || isset($names[$name])) {
                throw ValidationException::withMessages([
                    'file' => __('validation.offline-sync.wrong_file_name'),
                ]);
            }

            $names[$name] = true;
            abort_if(self::parseEntryName($name) === null, 400, __('Wrong screenshot file name'));

            $totalSize += $stat['size'];
            if ($totalSize > $maxSize) {
                throw self::tooLarge($maxSize);
            }
        }
    }

    /**
     * Decompresses every entry and discards the result, to reject a damaged archive or one that unpacks to more than
     * the limit before it is stored: the sizes in the zip headers checked by validate() can be forged.
     *
     * @throws ValidationException
     */
    public function scan(): void
    {
        $maxSize = (int)config('app.offline_screenshots.max_size');
        $remaining = $maxSize;

        for ($index = 0, $count = $this->zip->count(); $index < $count; $index++) {
            $this->copyEntry($index, null, $remaining, $maxSize);
        }
    }

    /**
     * Extracts the entry into the file, writing no more than the configured total size.
     *
     * @throws ValidationException
     */
    public function extractEntry(int $index, string $targetPath): void
    {
        $maxSize = (int)config('app.offline_screenshots.max_size');
        $remaining = $maxSize;

        $this->copyEntry($index, $targetPath, $remaining, $maxSize);
    }

    /**
     * ZipArchive::extractTo() writes whatever the entry decompresses to, so the entry is copied in chunks of at most
     * the remaining budget plus one byte, and rejected as soon as that extra byte is read. Without a target the bytes
     * are only counted.
     *
     * @throws ValidationException
     */
    private function copyEntry(int $index, ?string $targetPath, int &$remaining, int $maxSize): void
    {
        $stat = $this->zip->statIndex($index);
        $source = $stat === false ? false : $this->zip->getStreamIndex($index);
        abort_if($source === false, 400, __('Cannot open file.'));
        $written = 0;

        try {
            $target = null;
            if ($targetPath !== null) {
                $target = fopen($targetPath, 'wb');
                if ($target === false) {
                    throw new RuntimeException("Unable to create the extracted file for archive entry $index");
                }
            }

            try {
                while (!feof($source)) {
                    // Undecodable compressed data is reported as a warning and a failed read
                    $chunk = @fread($source, min(self::EXTRACTION_CHUNK_SIZE, $remaining + 1));
                    abort_if($chunk === false, 400, __('Cannot open file.'));

                    if (strlen($chunk) > $remaining) {
                        throw self::tooLarge($maxSize);
                    }

                    // A short write (e.g. a full disk) must not leave a truncated screenshot behind
                    if ($target !== null && fwrite($target, $chunk) !== strlen($chunk)) {
                        throw new RuntimeException("Unable to write the extracted file for archive entry $index");
                    }
                    $remaining -= strlen($chunk);
                    $written += strlen($chunk);
                }
            } finally {
                if ($target !== null) {
                    fclose($target);
                }
            }

            // A damaged entry can end early without a read error; never pass on a truncated screenshot
            abort_if($written !== $stat['size'], 400, __('Cannot open file.'));
        } finally {
            fclose($source);
        }
    }

    private static function tooLarge(int $maxSize): ValidationException
    {
        return ValidationException::withMessages([
            'file' => __('validation.offline-sync.too_large', [
                'max' => Number::fileSize($maxSize, maxPrecision: 2),
            ]),
        ]);
    }

    /**
     * A plain file name: not `.`, no directories, no parent references, no absolute or drive paths and no NUL bytes.
     */
    private static function isPlainEntryName(string $name): bool
    {
        return $name !== ''
            && $name !== '.'
            && strpbrk($name, "/\\\0") === false
            && !str_contains($name, '..')
            && preg_match('/^[A-Za-z]:/', $name) !== 1;
    }
}
