<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types\Objects;

use Closure;
use Manuylenko\Dumper\Types\Objects\Closure\TypeData;
use Manuylenko\Dumper\Types\ObjectType;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

class ClosureObject
{
    /**
     * Конструктор.
     */
    public function __construct(protected ObjectType $object)
    {
        //
    }

    /**
     * Рендерит содержимое объекта Closure.
     */
    public function render(Closure $object, string $uId): string
    {
        $out = '';

        $ref = new ReflectionFunction($object);

        $out .= '<a class="md_to-'.$uId.' md_toggle" title="Expand">>></a>';
        $out .= '<span class="md_content">';

        // Имя файла
        $out .= '<span class="md_row">';
        $out .= '<span class="md_property">file</span>';
        $out .= '<span class="md_operator">: </span>';
        $out .= '<span class="md_string">"'.$ref->getFileName().'"</span>';
        $out .= '</span>';

        // Номера строк
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();

        $out .= '<span class="md_row">';
        $out .= '<span class="md_property">'.($start < $end ? 'lines' : 'line').'</span>';
        $out .= '<span class="md_operator">: </span>';
        $out .= '<span class="md_number">'.($start < $end ? $start.'-'.$end : $start).'</span>';
        $out .= '</span>';

        // Входные параметры
        $out .= $this->renderVariable($ref->getParameters(), 'parameters');

        // Статические переменные
        $out .= $this->renderVariable($ref->getStaticVariables(), 'use');

        // Возвращаемые типы
        $types = $this->getTypesData($ref->getReturnType());

        if (count($types) > 0) {
            $out .= '<span class="md_row">';
            $out .= '<span class="md_property">return</span>';
            $out .= '<span class="md_operator">: </span>';
            $out .= $this->renderReturnTypes($types);
            $out .= '</span>';
        }

        $out .= '</span>';

        return $out;
    }

    /**
     * Рендерит возвращаемый тип.
     */
    protected function renderType(TypeData $rData): string
    {
        $out = '';

        if ($rData->builtin) {
            $out .= '<span class="md_type">'.$rData->names[0].'</span>';
        }
        else {
            $out .= '<span class="md_block">';

            foreach ($rData->names as &$name) {
                $name = $this->object->renderClass($name);
            }

            $out .= implode(' &amp; ', $rData->names);
            $out .= '</span>';
        }

        return $out;
    }

    /**
     * Рендерит возвращаемые типы для объекта Closure.
     */
    protected function renderReturnTypes(array $types): string
    {
        if (count($types) == 1) {
            return $this->renderType($types[0]);
        }

        $out = '';

        $uId = $this->object->getDumper()->genUId();

        $out .= '<span class="md_block">';
        $out .= '<span class="md_br-'.$uId.' md_braces" title="">[</span>';
        $out .= '<a class="md_to-'.$uId.' md_toggle" title="Expand">>></a>';
        $out .= '<span class="md_content">';

        foreach ($types as $type) {
            $out .= '<span class="md_row">';
            $out .= $this->renderType($type);
            $out .= '</span>';
        }

        $out .= '</span>';
        $out .= '<span class="md_br-'.$uId.' md_braces" title="">]</span>';
        $out .= '</span>';

        return $out;
    }

    /**
     * Получает массив данных типов.
     *
     * @return TypeData[]
     */
    protected function getTypesData(ReflectionType $refType): array
    {
        $types = [];

        switch (true) {
            case $refType instanceof ReflectionNamedType:
                $types[] = new TypeData($refType->isBuiltin(), [$refType->getName()]);
                if ($refType->allowsNull()) {
                    $types[] = new TypeData(true, ['null']);
                }
                break;
            case $refType instanceof ReflectionUnionType:
                /** @var ReflectionNamedType $type */
                foreach ($refType->getTypes() as $type) {
                    $types[] = new TypeData($type->isBuiltin(), [$type->getName()]);
                }
                break;
            case $refType instanceof ReflectionIntersectionType:
                $names = [];
                /** @var ReflectionNamedType $type */
                foreach ($refType->getTypes() as $type) {
                    $names[] = $type->getName();
                }
                $types[] = new TypeData(false, $names);
                break;
        }

        return $types;
    }

    /**
     * Рендерит значения переменных объекта Closure.
     */
    protected function renderVariable(array $vars, string $type): string
    {
        $out = '';

        $count = count($vars);

        if ($count > 0) {
            $uId = $this->object->getDumper()->genUId();

            $out .= '<span class="md_row">';
            $out .= '<span class="md_block">';
            $out .= '<span class="md_property">'.$type.'</span>';
            $out .= '<span class="md_operator">: </span>';
            $out .= '<span class="md_br-'.$uId.' md_braces" title="variables: '.$count.'">[</span>';
            $out .= '<a class="md_to-'.$uId.' md_toggle" title="Expand">>></a>';
            $out .= '<span class="md_content">';

            switch ($type) {
                case 'parameters':
                    foreach ($vars as $param) {
                        $out .= '<span class="md_row">';
                        $pType = ($pType = $param->getType()) ? $pType->getName() : '';
                        $out .= '<span class="md_property" title="'.$pType.'">$'.$param->getName().'</span>';

                        if ($param->isDefaultValueAvailable()) {
                            $out .= '<span class="md_operator"> = </span>';
                            $out .= $this->object->getDumper()->resolve($param->getDefaultValue());
                        }

                        $out .= '</span>';
                    }
                    break;
                case 'use':
                    foreach ($vars as $key => $value) {
                        $out .= '<span class="md_row">';
                        $out .= '<span class="md_property">$'.$key.'</span>';
                        $out .= '<span class="md_operator"> = </span>';
                        $out .= $this->object->getDumper()->resolve($value);
                        $out .= '</span>';
                    }
                    break;
            }

            $out .= '</span>';
            $out .= '<span class="md_br-'.$uId.' md_braces" title="variables: '.$count.'">]</span>';
            $out .= '</span>';
            $out .= '</span>';
        }

        return $out;
    }
}
