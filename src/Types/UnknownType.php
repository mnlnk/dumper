<?php

namespace Manuylenko\Dumper\Types;

class UnknownType extends Type
{
    /**
     * @return string
     */
    public static function render()
    {
        return '<span class="md_unknown">Неизвестный тип</span>';
    }
}
