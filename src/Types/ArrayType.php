<?php

namespace Manuylenko\Dumper\Types;

use Manuylenko\Dumper\Dumper;

class ArrayType extends Type
{
    /**
     * @var array
     */
    protected static $list = [];

    /**
     * @var array
     */
    protected static $br = [];


    /**
     * @param Dumper $dumper
     * @param array $array
     *
     * @return string
     */
    public static function render(Dumper $dumper, $array)
    {
        $count = count($array);
        $last = substr(strval($count), -1);

        switch ($last) {
            case '1':
                $ends = 'элемент';
                break;
            case '2':
            case '3':
            case '4':
                $ends = 'элемента';
                break;
            default:
                $ends = 'элементов';
        }

        $brId = static::getUid();

        $out = '<span class="md_block md_array" title="Массив: '.$count.' '.$ends.'">';
        $out .= '<span class="md_br-'.$brId.' brackets">[</span>';



        if (in_array($array, self::$list)) {
            $arrId = array_keys(self::$list, $array)[0];
            $recId = self::$br[$arrId];

            $out .= '<span class="md_re-'.$recId.' md_recursion" title="Рекурсия массива">&recursion</span>';
        } else {
            if ($count > 0) {
                $out .= '<a class="md_to-'.$brId.' md_toggle" title="Развернуть">>></a>';
                $out .= '<span class="md_content">';

                array_push(self::$list, $array);
                $arrId = array_keys(self::$list, $array)[0];
                self::$br[$arrId] = $brId;

                foreach ($array as $key => $value) {
                    $out .= '<span class="md_row" title="">';

                    $out .= is_numeric($key)
                        ? '<span class="md_number">'.$key.'</span>'
                        : '<span class="md_string">"'.$key.'"</span>';

                    $out .= '<span class="md_operator"> => </span>';
                    $out .= $dumper->resolve($value);
                    $out .= '</span>';
                }

                unset(self::$br[$arrId]);
                array_pop(self::$list);

                $out .= '</span>';
            }
        }

        $out .= '<span class="md_br-'.$brId.' brackets">]</span>';
        $out .= '</span>';

        return $out;
    }
}
