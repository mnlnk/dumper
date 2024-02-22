<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

class UnknownType extends Type
{
    /**
     * Рендерит неизвестный тип.
     */
    public static function render(): string
    {
        return '<span class="md_unknown" title="unknown">Unknown</span>';
    }
}
