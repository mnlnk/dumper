<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

class NumberType extends Type
{
    /**
     * Рендерит число.
     */
    public function render(int|float $num): string
    {
        if (is_double($num)) {
            if ($num == (int) $num) {
                $num .= '.0';
            }

            $type = 'float';
        }
        else {
            $type = 'int';
        }

        return '<span class="md_number" title="'.$type.'">'.$num.'</span>';
    }
}
