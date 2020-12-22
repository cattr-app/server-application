<?php

namespace App\Helpers;

class CatHelper
{
    public const CATS = [
        '=^._.^=',
        '(=｀ェ´=)',
        '(=^ ◡ ^=)',
        '/ᐠ｡ꞈ｡ᐟ\\',
        '/ᐠ.ꞈ.ᐟ\\',
        '✧/ᐠ-ꞈ-ᐟ\\',
        '(ﾐචᆽචﾐ)',
        '(=චᆽච=)',
        '(=ㅇᆽㅇ=)',
        '(=ㅇ༝ㅇ=)',
        '₍⸍⸌̣ʷ̣̫⸍̣⸌₎',
        '=＾ᵒ⋏ᵒ＾=',
        '( ⓛ ﻌ ⓛ *)',
        '(=ↀωↀ=)',
        '(=^･ω･^=)',
        '(=^･ｪ･^=)',
        'ㅇㅅㅇ'
    ];

    public static function getCat(): string
    {
        return self::CATS[array_rand(self::CATS)];
    }
}
