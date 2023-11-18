<?php

namespace Manuylenko\Dumper\Types;

class StringType extends Type
{
    /**
     * @var string
     */
    protected static $charset = 'UTF-8';

    /**
     * @var int
     */
    protected static $maxlength = 60;


    /**
     * @param string $string
     *
     * @return string
     */
    public static function render($string)
    {
        $length = mb_strlen($string, self::$charset);
        $last = substr(strval($length), -1);

        switch ($last) {
            case '1':
                $ends = 'символ';
                break;
            case '2':
            case '3':
            case '4':
                $ends = 'символа';
                break;
            default:
                $ends = 'символов';
        }

        $out = '<span class="block string" title="Строка: '.$length.' '.$ends.'">';

        if ($length > self::$maxlength) {
            $collapse = self::htmlspecialchars(self::replaceNel($string));
            $expand = self::htmlspecialchars(self::replaceNel(mb_substr($string, 0, self::$maxlength - 1, self::$charset)));
            $uId = self::getUid();

            $out .= '<span class="collapse">"'.$collapse.'" </span>';
            $out .= '<span class="expand">"'.$expand.'..." </span>';
            $out .= '<a class="to-'.$uId.' toggle" title="Развернуть">>></a>';
        } else {
            $out .= '"'.self::replaceNel($string).'"';
        }

        $out .= '</span>';

        return $out;
    }

    /**
     * Преобразует спец символы.
     *
     * @param string $string
     *
     * @return string
     */
    protected static function htmlspecialchars($string)
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, self::$charset);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected static function replaceNel($string)
    {
        $string = str_replace(
            [
                "\r\n",
                "\r",
                "\n"
            ],
            [
                '<span class="nel" title="Перенос строки Windows">\r\n</span><br>',
                '<span class="nel" title="Перенос строки MacOS">\r</span><br>',
                '<span class="nel" title="Перенос строки Unix">\n</span><br>',
            ],
            $string
        );

        $string = preg_replace('#(<br>)$#', '', $string);

        return $string;
    }
}
