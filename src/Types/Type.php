<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

use Manuylenko\Dumper\Dumper;

abstract class Type
{
    /**
     * Конструктор.
     */
    public function __construct(protected ?Dumper $dumper = null)
    {
        //
    }

    /**
     * Получает объект дампера.
     */
    public function getDumper(): ?Dumper
    {
        return $this->dumper;
    }
}
