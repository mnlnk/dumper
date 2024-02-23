<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types\Objects;

use Closure;
use Manuylenko\Dumper\Types\Objects\Closure\TypeData;
use Manuylenko\Dumper\Types\ObjectType;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
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

        $out .= '<a class="md-to-'.$uId.' md-toggle" title="Expand">>></a>';
        $out .= '<span class="md-content">';

        // Имя файла
        $out .= '<span class="md-row">';
        $out .= '<span class="md-property">file</span>';
        $out .= '<span class="md-operator">: </span>';
        $out .= '<span class="md-string">"'.$ref->getFileName().'"</span>';
        $out .= '</span>';

        // Номера строк
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();

        $out .= '<span class="md-row">';
        $out .= '<span class="md-property">'.($start < $end ? 'lines' : 'line').'</span>';
        $out .= '<span class="md-operator">: </span>';
        $out .= '<span class="md-number">'.($start < $end ? $start.'-'.$end : $start).'</span>';
        $out .= '</span>';

        // Входные параметры
        $out .= $this->renderVariable($ref->getParameters(), 'parameters');

        // Статические переменные
        $out .= $this->renderVariable($ref->getStaticVariables(), 'use');

        // Возвращаемые типы
        $out .= $this->renderReturnType($this->getTypesData($ref->getReturnType()));

        $out .= '</span>';

        return $out;
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

            $out .= '<span class="md-row">';
            $out .= '<span class="md-wrap">';
            $out .= '<span class="md-property">'.$type.'</span>';
            $out .= '<span class="md-operator">: </span>';
            $out .= '<span class="md-br-'.$uId.' md-braces" title="variables: '.$count.'">[</span>';
            $out .= '<a class="md-to-'.$uId.' md-toggle" title="Expand">>></a>';
            $out .= '<span class="md-content">';

            switch ($type) {
                case 'parameters':
                    foreach ($vars as $param) {
                        $out .= '<span class="md-row">';
                        $pType = $this->renderParameterType($param);
                        $out .= '<span class="md-property" title="'.$pType.'">$'.$param->getName().'</span>';

                        if ($param->isDefaultValueAvailable()) {
                            $out .= '<span class="md-operator"> = </span>';
                            $out .= $this->object->getDumper()->resolve($param->getDefaultValue());
                        }

                        $out .= '</span>';
                    }
                    break;
                case 'use':
                    foreach ($vars as $key => $value) {
                        $out .= '<span class="md-row">';
                        $out .= '<span class="md-property">$'.$key.'</span>';
                        $out .= '<span class="md-operator"> = </span>';
                        $out .= $this->object->getDumper()->resolve($value);
                        $out .= '</span>';
                    }
                    break;
            }

            $out .= '</span>';
            $out .= '<span class="md-br-'.$uId.' md-braces" title="variables: '.$count.'">]</span>';
            $out .= '</span>';
            $out .= '</span>';
        }

        return $out;
    }

    /**
     * Получает массив данных типов.
     *
     * @return TypeData[]
     */
    protected function getTypesData(?ReflectionType $refType): array
    {
        $types = [];

        if ($refType === null) {
            return $types;
        }

        switch (true) {
            case $refType instanceof ReflectionNamedType:
                $types[] = new TypeData($refType->isBuiltin(), [$refType->getName()]);
                if ($refType->getName() !== 'null' && $refType->allowsNull()) {
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
     * Рендерит тип параметра.
     */
    protected function renderParameterType(ReflectionParameter $param): string
    {
        $typeData = $this->getTypesData($param->getType());
        $countData = count($typeData);

        switch ($countData) {
            case 0:
                return 'undefined type';
            case 1:
                return implode(' & ', $typeData[0]->names);
            default:
                $names = [];
                foreach ($typeData as $data) {
                    $names[] = $data->names[0];
                }

                return implode(' | ', $names);
        }
    }

    /**
     * Рендерит возвращаемые типы для объекта Closure.
     *
     * @param TypeData[] $typesData
     */
    protected function renderReturnType(array $typesData): string
    {
        $out = '';
        $countData = count($typesData);

        if ($countData == 0) {
            return '';
        }

        $out .= '<span class="md-row">';
        $out .= '<span class="md-property">return</span>';
        $out .= '<span class="md-operator">: </span>';

        if ($countData == 1) {
            if ($typesData[0]->builtin) {
                $out .= '<span class="md-type">'.$typesData[0]->names[0].'</span>';
            }
            else {
                $out .= '<span class="md-wrap">';
                if (count($typesData[0]->names) == 1) {
                    $out .= $this->object->renderClass($typesData[0]->names[0]);
                }
                else {
                    foreach ($typesData[0]->names as &$name) {
                        $name = $this->object->renderClass($name);
                    }
                    $out .= implode('<span class="md-operator md-return" title="intersection">&amp;</span>', $typesData[0]->names);
                }
                $out  .= '</span>';
            }
        }
        else {
            $types = [];
            $out .= '<span class="md-wrap">';
            foreach ($typesData as $type) {
                if ($type->builtin) {
                    $types[] = '<span class="md-type">'.$type->names[0].'</span>';
                }
                else {
                    $types[] = $this->object->renderClass($type->names[0]);
                }
            }
            $out .= implode('<span class="md-operator md-return" title="union">|</span>', $types);
            $out  .= '</span>';
        }

        $out .= '</span>';

        return $out;
    }
}
