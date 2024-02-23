<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

class StringType extends Type
{
    /**
     * Кодировка строк.
     */
    protected string $charset = 'UTF-8';

    /**
     * Максимальная длинна строки в неразвернутом виде.
     */
    protected int $maxlength = 60;


    /**
     * Рендерит строку.
     */
    public function render(string $string): string
    {
        $length = mb_strlen($string, $this->charset);

        $out = '<span class="md-string md-wrap" title="string: '.$length.'">';

        if ($length > $this->maxlength) {
            $uId = $this->dumper->genUId();

            $collapse = $this->replaceNel($this->htmlspecialchars($string));
            $expand = $this->replaceNel($this->htmlspecialchars(mb_substr($string, 0, $this->maxlength - 1, $this->charset)));

            $out .= '<span class="md-collapse">"'.$collapse.'" </span>';
            $out .= '<span class="md-expand">"'.$expand.'..." </span>';
            $out .= '<a class="md-to-'.$uId.' md-toggle" title="Expand">>></a>';
        }
        else {
            $out .= '"'.$this->replaceNel($this->htmlspecialchars($string)).'"';
        }

        $out .= '</span>';

        return $out;
    }

    /**
     * Преобразует спец. символы в строке.
     */
    protected function htmlspecialchars(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $this->charset);
    }

    /**
     * Заменяет символы перевода строки на <br>.
     */
    protected function replaceNel(string $string): string
    {
        $string = str_replace(
            [
                "\r\n",
                "\r",
                "\n"
            ],
            [
                '<span class="md-nel" title="windows">\r\n</span><br>',
                '<span class="md-nel" title="mac">\r</span><br>',
                '<span class="md-nel" title="unix">\n</span><br>',
            ],
            $string
        );

        $string = preg_replace('#(<br>)$#', '', $string);

        return $string;
    }
}
