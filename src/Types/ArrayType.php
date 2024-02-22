<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

use Manuylenko\Dumper\Dumper;

class ArrayType extends Type
{
    /**
     * Массив массивов для поиска рекурсии.
     */
    protected static array $list = [];

    /**
     * Массив идентификаторов скобок массивов.
     */
    protected static array $br = [];


    /**
     * Рендерит массив.
     */
    public static function render(Dumper $dumper, array $array): string
    {
        $count = count($array);
        $brId = Dumper::getUid();

        $out = '<span class="md_block md_array">';
        $out .= '<span class="md_br-'.$brId.' md_brackets" title="array: '.$count.'">[</span>';

        if (in_array($array, static::$list)) {
            $arrId = array_keys(static::$list, $array)[0];
            $recId = static::$br[$arrId];

            $out .= '<span class="md_re-'.$recId.' md_recursion" title="recursion">&recursion</span>';
        }
        else {
            if ($count > 0) {
                $out .= '<a class="md_to-'.$brId.' md_toggle" title="Expand">>></a>';
                $out .= '<span class="md_content">';

                static::$list[] = $array;
                $arrId = array_keys(static::$list, $array)[0];
                static::$br[$arrId] = $brId;

                foreach ($array as $key => $value) {
                    $out .= '<span class="md_row">';

                    $out .= is_numeric($key)
                        ? '<span class="md_number">'.$key.'</span>'
                        : '<span class="md_string">"'.$key.'"</span>';

                    $out .= '<span class="md_operator"> => </span>';
                    $out .= $dumper->resolve($value);
                    $out .= '</span>';
                }

                unset(static::$br[$arrId]);
                array_pop(static::$list);

                $out .= '</span>';
            }
        }

        $out .= '<span class="md_br-'.$brId.' md_brackets" title="array: '.$count.'">]</span>';
        $out .= '</span>';

        return $out;
    }
}
