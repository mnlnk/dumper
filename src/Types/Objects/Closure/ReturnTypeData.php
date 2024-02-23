<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types\Objects\Closure;

class ReturnTypeData
{
    /**
     * Конструктор.
     */
    public function __construct(public bool $builtin, public array $names)
    {
        //
    }
}
