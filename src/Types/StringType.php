<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

class StringType extends Type
{
    /**
     * ..
     */
    protected static string $charset = 'UTF-8';

    /**
     * ..
     */
    protected static int $maxlength = 60;


    /**
     * ..
     */
    public static function render(string $string): string
    {
        $length = mb_strlen($string, self::$charset);
        $last = substr(strval($length), -1);

        $ends = match ($last) {
            '1' => 'символ',
            '2', '3', '4' => 'символа',
            default => 'символов'
        };

        $out = '<span class="md_block md_string" title="Строка: '.$length.' '.$ends.'">';

        if ($length > self::$maxlength) {
            $collapse = self::htmlspecialchars(self::replaceNel($string));
            $expand = self::htmlspecialchars(self::replaceNel(mb_substr($string, 0, self::$maxlength - 1, self::$charset)));
            $uId = self::getUid();

            $out .= '<span class="md_collapse">"'.$collapse.'" </span>';
            $out .= '<span class="md_expand">"'.$expand.'..." </span>';
            $out .= '<a class="md_to-'.$uId.' md_toggle" title="Развернуть">>></a>';
        } else {
            $out .= '"'.self::replaceNel($string).'"';
        }

        $out .= '</span>';

        return $out;
    }

    /**
     * Преобразует спец символы.
     */
    protected static function htmlspecialchars(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, self::$charset);
    }

    /**
     * ..
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
                '<span class="md_nel" title="Перенос строки Windows">\r\n</span><br>',
                '<span class="md_nel" title="Перенос строки MacOS">\r</span><br>',
                '<span class="md_nel" title="Перенос строки Unix">\n</span><br>',
            ],
            $string
        );

        $string = preg_replace('#(<br>)$#', '', $string);

        return $string;
    }
}
