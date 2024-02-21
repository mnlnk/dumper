<?php

namespace Manuylenko\Dumper\Types;

class NullType extends Type
{
    /**
     * @return string
     */
    public static function render() 
    {
        return '<span class="md_null">null</span>';
    }
}
