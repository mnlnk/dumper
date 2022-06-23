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

        $out = '<span class="block array" title="Массив: '.$count.' '.$ends.'">';
        $out .= '<span class="br-'.$brId.' brackets">[</span>';



        if (in_array($array, self::$list)) {
            $arrId = array_keys(self::$list, $array)[0];
            $recId = self::$br[$arrId];

            $out .= '<span class="re-'.$recId.' recursion" title="Рекурсия массива">&recursion</span>';
        } else {
            if ($count > 0) {
                $out .= '<a class="to-'.$brId.' toggle" title="Развернуть">>></a>';
                $out .= '<span class="content">';

                array_push(self::$list, $array);
                $arrId = array_keys(self::$list, $array)[0];
                self::$br[$arrId] = $brId;

                foreach ($array as $key => $value) {
                    $out .= '<span class="row" title="">';

                    $out .= is_numeric($key)
                        ? '<span class="number">'.$key.'</span>'
                        : '<span class="string">"'.$key.'"</span>';

                    $out .= '<span class="operator"> => </span>';
                    $out .= $dumper->resolve($value);
                    $out .= '</span>';
                }

                unset(self::$br[$arrId]);
                array_pop(self::$list);

                $out .= '</span>';
            }
        }

        $out .= '<span class="br-'.$brId.' brackets">]</span>';
        $out .= '</span>';

        return $out;
    }
}
