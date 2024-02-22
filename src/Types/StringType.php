<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

use Manuylenko\Dumper\Dumper;

class StringType extends Type
{
    /**
     * Кодировка строк.
     */
    protected static string $charset = 'UTF-8';

    /**
     * Максимальная длинна строки в неразвернутом виде.
     */
    protected static int $maxlength = 60;


    /**
     * Рендерит строку.
     */
    public static function render(string $string): string
    {
        $length = mb_strlen($string, static::$charset);

        $out = '<span class="md_block md_string" title="string: '.$length.'">';

        if ($length > static::$maxlength) {
            $uId = Dumper::getUid();

            $collapse = static::htmlspecialchars(static::replaceNel($string));
            $expand = static::htmlspecialchars(static::replaceNel(mb_substr($string, 0, static::$maxlength - 1, static::$charset)));

            $out .= '<span class="md_collapse">"'.$collapse.'" </span>';
            $out .= '<span class="md_expand">"'.$expand.'..." </span>';
            $out .= '<a class="md_to-'.$uId.' md_toggle" title="Expand">>></a>';
        }
        else {
            $out .= '"'.static::replaceNel($string).'"';
        }

        $out .= '</span>';

        return $out;
    }

    /**
     * Преобразует спец. символы в строке.
     */
    protected static function htmlspecialchars(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, static::$charset);
    }

    /**
     * Заменяет символы перевода строки на <br>.
     */
    protected static function replaceNel(string $string): string
    {
        $string = str_replace(
            [
                "\r\n",
                "\r",
                "\n"
            ],
            [
                '<span class="md_nel" title="windows">\r\n</span><br>',
                '<span class="md_nel" title="mac">\r</span><br>',
                '<span class="md_nel" title="unix">\n</span><br>',
            ],
            $string
        );

        $string = preg_replace('#(<br>)$#', '', $string);

        return $string;
    }
}
