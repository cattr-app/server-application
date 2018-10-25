<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

/**
 * Class FileHelper
 *
 * @package App\Helpers
 */
class FileHelper
{
    /**
     * @return string[]
     */
    public function getScripts(): array
    {
        $files = scandir(public_path() . DIRECTORY_SEPARATOR . 'js');
        $files = array_filter($files, function ($file) {
            return preg_match('/.*\.bundle\.js$/', $file);
        });

        $weight = [
            'inline.bundle.js'    => -5,
            'polyfills.bundle.js' => -4,
            'styles.bundle.js'    => -3,
            'scripts.bundle.js'   => -2,
            'vendor.bundle.js'    => -1,
        ];

        usort($files, function ($a, $b) use ($weight) {
            $a = isset($weight[$a]) ? $weight[$a] : 0;
            $b = isset($weight[$b]) ? $weight[$b] : 0;
            return $a - $b;
        });

        return $files;
    }

    /**
     * @return string[]
     */
    public function getStyles(): array
    {
        $files = scandir(public_path() . DIRECTORY_SEPARATOR . 'js');
        return array_filter($files, function ($file) {
            return preg_match('/.*\.bundle\.css$/', $file);
        });
    }
}
