<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

class ArrayType extends Type
{
    /**
     * Отрисованные массивы.
     *
     * @var array[]
     */
    protected static array $renderList = [];

    /**
     * Идентификаторы скобок массивов.
     *
     * @var string[]
     */
    protected static array $brackets = [];


    /**
     * Рендерит массив.
     */
    public function render(array $array): string
    {
        $uId = $this->dumper->genUId();
        $count = count($array);

        $out = '<span class="md-array md-block">';
        $out .= '<span class="md-br-'.$uId.' md-brackets" title="array: '.$count.'">[</span>';

        if (in_array($array, static::$renderList)) {
            $arrayId = array_keys(static::$renderList, $array)[0];
            $recursionId = static::$brackets[$arrayId];

            $out .= '<span class="md-re-'.$recursionId.' md-recursion" title="recursion">&recursion</span>';
        }
        else {
            if ($count > 0) {
                $out .= '<a class="md-to-'.$uId.' md-toggle" title="Expand">>></a>';
                $out .= '<span class="md-content">';

                static::$renderList[] = $array;
                $arrayId = array_keys(static::$renderList, $array)[0];
                static::$brackets[$arrayId] = $uId;

                foreach ($array as $key => $value) {
                    $out .= '<span class="md-row">';
                    $out .= is_numeric($key) ? '<span class="md-number">'.$key.'</span>' : '<span class="md-string">"'.$key.'"</span>';
                    $out .= '<span class="md-operator"> => </span>';
                    $out .= $this->dumper->resolve($value);
                    $out .= '</span>';
                }

                unset(static::$brackets[$arrayId]);
                array_pop(static::$renderList);

                $out .= '</span>';
            }
        }

        $out .= '<span class="md-br-'.$uId.' md-brackets" title="array: '.$count.'">]</span>';
        $out .= '</span>';

        return $out;
    }
}
