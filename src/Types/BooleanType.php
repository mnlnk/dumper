<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

class BooleanType extends Type
{
    /**
     * ..
     */
    public static function render(bool $bool): string
    {
        return '<span class="md_boolean" title="Тип: bool">'.($bool ? 'true' : 'false').'</span>';
    }
}
