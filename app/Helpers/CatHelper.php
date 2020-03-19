<?php

namespace App\Helpers;

class CatHelper
{
    protected static array $cats = [
        '=^._.^=', '(=｀ェ´=)', '(=^ ◡ ^=)',
        '/ᐠ｡ꞈ｡ᐟ\\', '/ᐠ.ꞈ.ᐟ\\', '✧/ᐠ-ꞈ-ᐟ\\',
        '(ﾐචᆽචﾐ)', '(=චᆽච=)', '(=ㅇᆽㅇ=)', '(=ㅇ༝ㅇ=)',
        '₍⸍⸌̣ʷ̣̫⸍̣⸌₎',
        '=＾ᵒ⋏ᵒ＾=',
        '( ⓛ ﻌ ⓛ *)', ''
    ];

    public function getCat(): string
    {
        return self::$cats[array_rand(self::$cats)];
    }
}
