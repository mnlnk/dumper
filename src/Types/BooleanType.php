<?php

namespace Manuylenko\Dumper\Types;

class BooleanType extends Type
{
    /**
     * @param bool $bool
     *
     * @return string
     */
    public static function render($bool)
    {
        return '<span class="boolean" title="Тип: bool">'.($bool ? 'true' : 'false').'</span>';
    }
}
