<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

class NullType extends Type
{
    /**
     * Рендерит нулевое значение.
     */
    public static function render(): string
    {
        return '<span class="md_null">null</span>';
    }
}
