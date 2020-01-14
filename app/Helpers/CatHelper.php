<?php

namespace App\Helpers;

/**
 * Class CatHelper
 *
 * @package App\Helpers
 */
class CatHelper
{
    /**
     * @var string[]
     */
    protected static $cats = [
        '=^._.^=', '(=｀ェ´=)', '(=^ ◡ ^=)',
        '/ᐠ｡ꞈ｡ᐟ\\', '/ᐠ.ꞈ.ᐟ\\', '✧/ᐠ-ꞈ-ᐟ\\',
        '(ﾐචᆽචﾐ)', '(=චᆽච=)', '(=ㅇᆽㅇ=)', '(=ㅇ༝ㅇ=)',
        '₍⸍⸌̣ʷ̣̫⸍̣⸌₎',
        '=＾ᵒ⋏ᵒ＾=',
        '( ⓛ ﻌ ⓛ *)', ''
    ];

    /**
     * @return string
     */
    public function getCat(): string
    {
        return self::$cats[array_rand(self::$cats)];
    }
}
