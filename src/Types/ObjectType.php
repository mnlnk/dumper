<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

use Closure;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionUnionType;

class ObjectType extends Type
{
    /**
     * Размер пространства имен в сокращенном виде.
     */
    protected int $shortNamespaceLength = 4;

    /**
     * Отрисованные объекты.
     *
     * @var object[]
     */
    protected static array $renderList = [];

    /**
     * Идентификаторы скобок объектов.
     *
     * @var string[]
     */
    protected static array $braces = [];


    /**
     * Рендерит объект.
     */
    public function render(object $object): string
    {
        $objId = (string) spl_object_id($object);

        $out  = '<span class="md_block md_object">';
        $out .= $this->renderClass(get_class($object));
        $out .= ' <span class="md_ha-'.$objId.' md_hash" title="id">#'.$objId.'</span> ';

        if (in_array($object, static::$renderList)) {
            $recId = static::$braces[$objId];

            $out .= '<span class="md_braces" title="object">{</span>';
            $out .= '<span class="md_re-'.$recId.' md_recursion" title="recursion">&recursion</span>';
            $out .= '<span class="md_braces" title="object">}</span>';
        }
        else {
            $uId = $this->dumper->getUId();

            static::$renderList[] = $object;
            static::$braces[$objId] = $uId;

            $out .= '<span class="md_br-'.$uId.' md_braces" title="object">{</span>';
            $out .= $object instanceof Closure ? $this->renderClosure($object, $uId) : $this->renderObject($object, $uId);
            $out .= '<span class="md_br-'.$uId.' md_braces" title="object">}</span>';

            unset(static::$braces[$objId]);
            array_pop(static::$renderList);
        }

        $out .= '</span>';

        return $out;
    }

    /**
     * Рендерит пространство имен и имя класса.
     */
    protected function renderClass(string $class): string
    {
        $out = '';

        $separator = mb_strrpos($class, '\\');

        if ($separator > 0) {
            $namespace = mb_substr($class, 0, $separator);
            $class = mb_substr($class, $separator + 1);

            $out .= '<span class="md_namespace" title="namespace"';

            if ($this->shortNamespaceLength < mb_strlen($namespace) - 3) {
                $shortNamespace = mb_substr($namespace, 0, $this->shortNamespaceLength);

                $out .= ' data-ns="'.$namespace.'\\">'.$shortNamespace.'...';
            }
            else {
                $out .= '>'.$namespace;
            }

            $out .= '\\</span>';
        }

        $out .= '<span class="md_class" title="class">'.$class.'</span>';

        return $out;
    }

    /**
     * Рендерит содержимое объекта.
     */
    protected function renderObject(object $object, string $uId): string
    {
        $out = '';

        $props = (new ReflectionObject($object))->getProperties();

        if (count($props) > 0) {
            $out .= '<a class="md_to-'.$uId.' md_toggle" title="Expand">>></a>';
            $out .= '<span class="md_content">';

            foreach ($props as $prop) {
                $modifier = '';
                $title = '';

                switch (true) {
                    case !$prop->isDefault():
                        $modifier = '=';
                        $title = 'public, dynamic';
                        break;
                    case $prop->isPublic():
                        $modifier = '+';
                        $title = 'public';
                        break;
                    case $prop->isPrivate():
                        $modifier = '-';
                        $title = 'private';
                        break;
                    case $prop->isProtected():
                        $modifier = '#';
                        $title = 'protected';
                        break;
                }

                $out .= '<span class="md_row">';

                if ($prop->isStatic()) {
                    $out .= '<span class="md_modifier" title="'.$title.' static">('.$modifier.')</span>';
                }
                else {
                    $out .= '<span class="md_modifier" title="'.$title.'">';
                    $out .= '<span class="md_parentheses">(</span>';
                    $out .= $modifier;
                    $out .= '<span class="md_parentheses">)</span>';
                    $out .= '</span>';
                }

                $out .= ' ';
                $out .= '<span class="md_property">$'.$prop->getName().'</span>';
                $out .= '<span class="md_operator"> = </span>';

                if ($prop->isInitialized($object)) {
                    $out .= $this->dumper->resolve($prop->getValue($object));
                }
                else {
                    $out .= '<span class="md_not_init" title="uninitialized">#E#</span>';
                }

                $out .= '</span>';
            }

            $out .= '</span>';
        }

        return $out;
    }

    /**
     * Рендерит содержимое объекта Closure.
     */
    protected function renderClosure(object $object, string $uId): string
    {
        $out = '';

        $reflection = new ReflectionFunction($object);

        $out .= '<a class="md_to-'.$uId.' md_toggle" title="Expand">>></a>';
        $out .= '<span class="md_content">';

        // Имя файла
        $out .= '<span class="md_row">';
        $out .= '<span class="md_property">file</span>';
        $out .= '<span class="md_operator">: </span>';
        $out .= '<span class="md_string">"'.$reflection->getFileName().'"</span>';
        $out .= '</span>';

        // Номера строк
        $start = $reflection->getStartLine();
        $end = $reflection->getEndLine();

        $out .= '<span class="md_row">';
        $out .= '<span class="md_property">'.($start < $end ? 'lines' : 'line').'</span>';
        $out .= '<span class="md_operator">: </span>';
        $out .= '<span class="md_number">'.($start < $end ? $start.'-'.$end : $start).'</span>';
        $out .= '</span>';

        // Входные параметры
        $out .= $this->renderVariable($reflection->getParameters(), 'parameters');

        // Статические переменные
        $out .= $this->renderVariable($reflection->getStaticVariables(), 'use');

        // Возвращаемые типы
        $types = $this->getReturnTypes($reflection);

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
     *
     * $type[0] -> builtin
     * $type[1] -> names
     */
    protected function renderType(array $type): string
    {
        $out = '';

        if ($type[0]) {
            $out .= '<span class="md_type">'.$type[1][0].'</span>';
        }
        else {
            $out .= '<span class="md_block">';

            if (count($type[1]) == 1) {
                $out .= $this->renderClass($type[1][0]);
            }
            else {
                foreach ($type[1] as &$class) $class = $this->renderClass($class);

                $out .= implode(' &amp; ', $type[1]);
            }

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

        $uId = $this->dumper->getUId();

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
     * Получает список возвращаемых типов.
     *
     * Return:
     *  [[true, ['string']]]                     // string
     *  [[true, ['string']], [true, ['null']]]   // ?string
     *  [[true, ['string']], [true, ['int']]]    // string|int
     *  [[false, ['Iterator', 'Countable']]]     // Iterator&Countable
     *  [[false, ['Closure']]]                   // Closure
     *  [[false, ['Closure']], [true, ['null']]] // ?Closure
     */
    protected function getReturnTypes(ReflectionFunction $ref): array
    {
        $types = [];
        $return = $ref->getReturnType();

        switch (true) {
            case $return instanceof ReflectionNamedType:
                $types[] = [$return->isBuiltin(), [$return->getName()]];
                if ($return->allowsNull()) {
                    $types[] = [true, ['null']];
                }
                break;
            case $return instanceof ReflectionUnionType:
                /** @var ReflectionNamedType $type */
                foreach ($return->getTypes() as $type) {
                    $types[] = [$type->isBuiltin(), [$type->getName()]];
                }
                break;
            case $return instanceof ReflectionIntersectionType:
                $names = [];
                /** @var ReflectionNamedType $type */
                foreach ($return->getTypes() as $type) {
                    $names[] = $type->getName();
                }
                $types[] = [false, $names];
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
            $uId = $this->dumper->getUId();

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
                            $out .= $this->dumper->resolve($param->getDefaultValue());
                        }

                        $out .= '</span>';
                    }
                    break;
                case 'use':
                    foreach ($vars as $key => $value) {
                        $out .= '<span class="md_row">';
                        $out .= '<span class="md_property">$'.$key.'</span>';
                        $out .= '<span class="md_operator"> = </span>';
                        $out .= $this->dumper->resolve($value);
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
