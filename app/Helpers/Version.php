<?php

namespace App\Helpers;

use JsonException;
use Module;
use InvalidArgumentException;

class Version
{
    protected int $major;
    protected int $minor;
    protected int $patch;
    protected ?string $pre;

    protected ?string $module;

    /**
     * Version constructor.
     *
     * @param string|null $module
     *
     * @throws JsonException
     */
    public function __construct(?string $module = null)
    {
        $this->module = $module;

        preg_match(
            '/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)'
            .
            '(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))'
            .
            '?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/',
            $this->readComposerJson()['version'] ?? '0.0.0',
            $matches
        );

        $this->major = (int)$matches['major'];
        $this->minor = (int)$matches['minor'];
        $this->patch = (int)$matches['patch'];
        $this->pre = $matches['prerelease'] ?? null;
    }

    /**
     * @return array
     * @throws JsonException
     */
    private function readComposerJson(): array
    {
        return json_decode(
            file_get_contents(self::resolveModuleComposerJsonPath($this->module)),
            true,
            512,
            JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR
        );
    }

    private static function resolveModuleComposerJsonPath(?string $module = null): string
    {
        if (!$module) {
            return base_path('composer.json');
        }

        $foundModule = Module::find($module);
        if (!$foundModule) {
            throw new InvalidArgumentException('No such module: ' . $module);
        }

        return $foundModule->getPath() . '/' . 'composer.json';
    }

    /**
     * @return $this
     * @throws JsonException
     */
    public function incrementMajor(): self
    {
        $this->major++;
        $this->minor = 0;
        $this->patch = 0;
        $this->pre = null;

        return $this->save();
    }

    /**
     * @return $this
     * @throws JsonException
     */
    protected function save(): self
    {
        $content = $this->readComposerJson();
        $content['version'] = $this->__toString();
        $this->writeComposerJson($content);

        return $this;
    }

    public function __toString(): string
    {
        $version = implode('.', [$this->major, $this->minor, $this->patch]);
        $version .= isset($this->pre) ? '-' . $this->pre : '';

        return $version;
    }

    /**
     * @param array $content
     *
     * @throws JsonException
     */
    private function writeComposerJson(array $content): void
    {
        file_put_contents(
            self::resolveModuleComposerJsonPath($this->module),
            json_encode(
                $content,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                512
            )
        );
    }

    /**
     * @return $this
     * @throws JsonException
     */
    public function incrementMinor(): self
    {
        $this->minor++;
        $this->patch = 0;
        $this->pre = null;

        return $this->save();
    }

    /**
     * @return $this
     * @throws JsonException
     */
    public function incrementPatch(): self
    {
        $this->patch++;
        $this->pre = null;

        return $this->save();
    }

    /**
     * @return $this
     * @throws JsonException
     */
    public function incrementPre(): self
    {
        (isset($this->pre)) ? $this->pre++ : $this->pre = 1;

        return $this->save();
    }

    /**
     * @return $this
     * @throws JsonException
     */
    public function clearPre(): self
    {
        $this->pre = null;

        return $this->save();
    }

    public function getMajor(): int
    {
        return $this->major;
    }

    public function getMinor(): int
    {
        return $this->minor;
    }

    public function getPatch(): int
    {
        return $this->patch;
    }

    public function getPre(): ?int
    {
        return $this->pre;
    }
}
