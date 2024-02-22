<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

use Closure;
use ReflectionFunction;
use ReflectionObject;
use Manuylenko\Dumper\Dumper;

class ObjectType extends Type
{
    /**
     * Длинна пространства имен в сокращенном виде.
     */
    protected static int $shortNamespaceLength = 4;

    /**
     * Массив объектов для поиска рекурсии.
     */
    protected static array $list = [];

    /**
     * Массив идентификаторов скобок объектов.
     */
    protected static array $br = [];


    /**
     * Рендерит объект.
     */
    public static function render(Dumper $dumper, object $object): string
    {
        $objId = (string) spl_object_id($object);

        $out  = '<span class="md_block md_object">';
        $out .= ''.static::renderClass($object);
        $out .= ' <span class="md_ha-'.$objId.' md_hash" title="Идентификатор объекта">#'.$objId.'</span> ';

        if (in_array($object, static::$list)) {
            $recId = static::$br[$objId];

            $out .= '<span class="md_braces">{</span>';
            $out .= '<span class="md_re-'.$recId.' md_recursion" title="Рекурсия объекта">&recursion</span>';
            $out .= '<span class="md_braces">}</span>';
        }
        else {
            $brId = Dumper::getUid();

            static::$list[] = $object;
            static::$br[$objId] = $brId;

            $out .= '<span class="md_br-'.$brId.' md_braces">{</span>';

            $out .= $object instanceof Closure
                ? static::renderClosure($object, $brId, $dumper)
                : static::renderObject($object, $brId, $dumper);

            $out .= '<span class="md_br-'.$brId.' md_braces">}</span>';

            unset(static::$br[$objId]);
            array_pop(static::$list);
        }

        $out .= '</span>';

        return $out;
    }

    /**
     * Рендерит пространство имен и имя класса.
     */
    protected static function renderClass(object $object): string
    {
        $out = '';

        $class = get_class($object);
        $separator = strrpos($class, '\\');

        if ($separator > 0) {
            $namespace = substr($class, 0, $separator);
            $class = substr($class, $separator + 1);

            if (static::$shortNamespaceLength < strlen($namespace) - 3) {
                $shortNamespace = substr($namespace, 0, static::$shortNamespaceLength);

                $out .= '<span class="md_namespace" title="Пространство имен" data-ns="'.$namespace.'\\">';
                $out .= $shortNamespace.'...\\';
                $out .= '</span>';
            }
            else {
                $out .= '<span class="md_namespace" title="Пространство имен">'.$namespace.'\\</span>';
            }
        }

        $out .= '<span class="md_class" title="Название класса">'.$class.'</span>';

        return $out;
    }

    /**
     * Рендерит содержимое объекта.
     */
    protected static function renderObject(object $object, string $uId, Dumper $dumper): string
    {
        $out = '';

        $props = (new ReflectionObject($object))->getProperties();

        if (count($props) > 0) {
            $out .= '<a class="md_to-'.$uId.' md_toggle" title="Развернуть">>></a>';
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
                    $out .= '<span class="md_parentheses">(</span>';
                    $out .= '<span class="md_modifier" title="'.$title.'">'.$modifier.'</span>';
                    $out .= '<span class="md_parentheses">)</span>';
                }

                $out .= ' ';
                $out .= '<span class="md_property">$'.$prop->getName().'</span>';
                $out .= '<span class="md_operator"> = </span>';

                if ($prop->isInitialized($object)) {
                    $out .= $dumper->resolve($prop->getValue($object));
                }
                else {
                    $out .= '<span class="md_not_init" title="Свойство объекта НЕ инициализировано">#E#</span>';
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
    protected static function renderClosure(object $object, string $uId, Dumper $dumper): string
    {
        $out = '';

        $reflection = new ReflectionFunction($object);

        $out .= '<a class="md_to-'.$uId.' md_toggle" title="Развернуть">>></a>';
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
        $out .= static::renderVariable($reflection->getParameters(), 'parameters', $dumper);

        // Статические переменные
        $out .= static::renderVariable($reflection->getStaticVariables(), 'use', $dumper);

        // Возвращаемый тип
        if ($type = $reflection->getReturnType()) {
            $out .= '<span class="md_row">';
            $out .= '<span class="md_property">return</span>';
            $out .= '<span class="md_operator">: </span>';
            $out .= '<span class="md_type">'.$type.'</span>';
            $out .= '</span>';
        }

        $out .= '</span>';

        return $out;
    }

    /**
     * Рендерит значения переменных объекта Closure.
     */
    protected static function renderVariable(array $vars, string $type, Dumper $dumper): string
    {
        $out = '';

        $count = count($vars);

        if ($count > 0) {
            $uid = Dumper::getUid();

            $out .= '<span class="md_row">';
            $out .= '<span class="md_block">';
            $out .= '<span class="md_property">'.$type.'</span>';
            $out .= '<span class="md_operator">: </span>';
            $out .= '<span class="md_br-'.$uid.' md_braces">{</span>';
            $out .= '<a class="md_to-'.$uid.' md_toggle" title="Развернуть">>></a>';
            $out .= '<span class="md_content">';

            switch ($type) {
                case 'parameters':
                    foreach ($vars as $param) {
                        $out .= '<span class="md_row">';
                        $pType = ($pType = $param->getType()) ? $pType->getName() : '';
                        $out .= '<span class="md_property" title="'.$pType.'">$'.$param->getName().'</span>';

                        if ($param->isDefaultValueAvailable()) {
                            $out .= '<span class="md_operator"> = </span>';
                            $out .= $dumper->resolve($param->getDefaultValue());
                        }

                        $out .= '</span>';
                    }
                    break;
                case 'use':
                    foreach ($vars as $key => $value) {
                        $out .= '<span class="md_row">';
                        $out .= '<span class="md_property">$'.$key.'</span>';
                        $out .= '<span class="md_operator"> = </span>';
                        $out .= $dumper->resolve($value);
                        $out .= '</span>';
                    }
                    break;
            }

            $out .= '</span>';
            $out .= '<span class="md_br-'.$uid.' md_braces">}</span>';
            $out .= '</span>';
            $out .= '</span>';
        }

        return $out;
    }
}
