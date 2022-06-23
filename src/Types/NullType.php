<?php

namespace Manuylenko\Dumper\Types;

class NullType extends Type
{
    /**
     * @return string
     */
    public static function render() 
    {
        return '<span class="null">null</span>';
    }
}
