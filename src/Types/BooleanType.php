<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

class BooleanType extends Type
{
    /**
     * Рендерит значение типа bool.
     */
    public function render(bool $bool): string
    {
        return '<span class="md-boolean" title="bool">'.($bool ? 'true' : 'false').'</span>';
    }
}
