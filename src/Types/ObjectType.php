<?php

namespace Manuylenko\Dumper\Types;

use Closure;
use ReflectionFunction;
use ReflectionObject;
use Manuylenko\Dumper\Dumper;

class ObjectType extends Type
{
    /**
     * @var int
     */
    protected static $shortNamespaceLength = 4;

    /**
     * @var array
     */
    protected static $list = [];

    /**
     * @var array
     */
    protected static $br = [];


    /**
     * @param Dumper $dumper
     * @param object $object
     *
     * @return string
     */
    public static function render(Dumper $dumper, $object)
    {
        $objId = self::getObjectId($object);

        $out  = '<span class="md_block md_object">';
        $out .= ''.self::renderClass($object);
        $out .= ' <span class="md_ha-'.$objId.' md_hash" title="Идентификатор объекта">#'.$objId.'</span> ';

        if (in_array($object, self::$list)) {
            $recId = self::$br[$objId];

            $out .= '<span class="md_braces">{</span>';
            $out .= '<span class="md_re-'.$recId.' md_recursion" title="Рекурсия объекта">&recursion</span>';
            $out .= '<span class="md_braces">}</span>';

        } else {
            $brId = static::getUid();

            array_push(self::$list, $object);
            self::$br[$objId] = $brId;

            $out .= '<span class="md_br-'.$brId.' md_braces">{</span>';

            switch (true) {
                case $object instanceof Closure:
                    $out .= self::renderClosure($object, $brId, $dumper);
                    break;
                default:
                    $out .= self::renderObject($object, $brId, $dumper);
            }

            $out .= '<span class="md_br-'.$brId.' md_braces">}</span>';

            unset(self::$br[$objId]);
            array_pop(self::$list);
        }

        $out .= '</span>';

        return $out;
    }

    /**
     * @param object $object
     *
     * @return string
     */
    protected static function renderClass($object)
    {
        $out = '';

        $class = get_class($object);
        $separator = strrpos($class, '\\');

        if ($separator > 0) {
            $namespace = substr($class, 0, $separator);
            $class = substr($class, $separator + 1);

            if (self::$shortNamespaceLength < strlen($namespace) - 3) {
                $shortNamespace = substr($namespace, 0, self::$shortNamespaceLength);
                $out .= '<span class="md_namespace" title="Пространство имен" data-ns="'.$namespace.'\\">';
                $out .= $shortNamespace.'...\\';
                $out .= '</span>';
            } else {
                $out .= '<span class="md_namespace" title="Пространство имен">'.$namespace.'\\</span>';
            }
        }

        $out .= '<span class="md_class" title="Название класса">';
        $out .= $class;
        $out .= '</span>';

        return $out;
    }

    /**
     * @param object $object
     *
     * @return int|string
     */
    protected static function getObjectId($object)
    {
        if (version_compare('7.2', phpversion(), '<=')) {
            return spl_object_id($object);
        } else {
            return substr(md5(spl_object_hash($object)), -4);
        }
    }

    /**
     * @param object $object
     * @param string $uId
     * @param Dumper $dumper
     *
     * @return string
     */
    protected static function renderObject($object, $uId, Dumper $dumper)
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
                    case ! $prop->isDefault():
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

                $prop->setAccessible(true);

                $out .= '<span class="md_row">';

                if ($prop->isStatic()) {
                    $out .= '<span class="md_modifier" title="'.$title.' static">('.$modifier.')</span>';
                } else {
                    $out .= '<span class="md_parentheses">(</span>';
                    $out .= '<span class="md_modifier" title="'.$title.'">'.$modifier.'</span>';
                    $out .= '<span class="md_parentheses">)</span>';
                }

                $out .= ' ';
                $out .= '<span class="md_property">$'.$prop->getName().'</span>';
                $out .= '<span class="md_operator"> = </span>';
                if ($prop->isInitialized($object)) {
                    $out .= $dumper->resolve($prop->getValue($object));
                } else {
                    $out .= '<span class="md_not_init" title="Свойство объекта НЕ инициализировано">#E#</span>';
                }
                $out .= '</span>';
            }

            $out .= '</span>';
        }

        return $out;
    }

    /**
     * @param object $object
     * @param string $uId
     * @param Dumper $dumper
     *
     * @return string
     */
    protected static function renderClosure($object, $uId, Dumper $dumper)
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
        $out .= self::renderVariable($reflection->getParameters(), 'parameters', $dumper);

        // Статические переменные
        $out .= self::renderVariable($reflection->getStaticVariables(), 'use', $dumper);

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
     * @param array $vars
     * @param string $type
     * @param Dumper $dumper
     *
     * @return string
     */
    protected static function renderVariable(array $vars, $type, Dumper $dumper)
    {
        $out = '';
        $count = count($vars);

        if ($count > 0) {
            $uid = static::getUid();

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
