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

        $out  = '<span class="block object">';
        $out .= ''.self::renderClass($object);
        $out .= ' <span class="ha-'.$objId.' hash" title="Идентификатор объекта">#'.$objId.'</span> ';

        if (in_array($object, self::$list)) {
            $recId = self::$br[$objId];

            $out .= '<span class="braces">{</span>';
            $out .= '<span class="re-'.$recId.' recursion" title="Рекурсия объекта">&recursion</span>';
            $out .= '<span class="braces">}</span>';

        } else {
            $brId = static::getUid();

            array_push(self::$list, $object);
            self::$br[$objId] = $brId;

            $out .= '<span class="br-'.$brId.' braces">{</span>';

            switch (true) {
                case $object instanceof Closure:
                    $out .= self::renderClosure($object, $brId, $dumper);
                    break;
                default:
                    $out .= self::renderObject($object, $brId, $dumper);
            }

            $out .= '<span class="br-'.$brId.' braces">}</span>';

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
                $out .= '<span class="namespace" title="Пространство имен" data-ns="'.$namespace.'\\">';
                $out .= $shortNamespace.'...\\';
                $out .= '</span>';
            } else {
                $out .= '<span class="namespace" title="Пространство имен">'.$namespace.'\\</span>';
            }
        }

        $out .= '<span class="class" title="Название класса">';
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

            $out .= '<a class="to-'.$uId.' toggle" title="Развернуть">>></a>';
            $out .= '<span class="content">';

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

                $out .= '<span class="row">';

                if ($prop->isStatic()) {
                    $out .= '<span class="modifier" title="'.$title.' static">('.$modifier.')</span>';
                } else {
                    $out .= '<span class="parentheses">(</span>';
                    $out .= '<span class="modifier" title="'.$title.'">'.$modifier.'</span>';
                    $out .= '<span class="parentheses">)</span>';
                }

                $out .= ' ';
                $out .= '<span class="property">$'.$prop->getName().'</span>';
                $out .= '<span class="operator"> = </span>';
                if ($prop->isInitialized($object)) {
                    $out .= $dumper->resolve($prop->getValue($object));
                } else {
                    $out .= '<span class="not_init" title="Свойство объекта НЕ инициализировано">#E#</span>';
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

        $out .= '<a class="to-'.$uId.' toggle" title="Развернуть">>></a>';
        $out .= '<span class="content">';

        // Имя файла
        $out .= '<span class="row">';
        $out .= '<span class="property">file</span>';
        $out .= '<span class="operator">: </span>';
        $out .= '<span class="string">"'.$reflection->getFileName().'"</span>';
        $out .= '</span>';

        // Номера строк
        $start = $reflection->getStartLine();
        $end = $reflection->getEndLine();

        $out .= '<span class="row">';
        $out .= '<span class="property">'.($start < $end ? 'lines' : 'line').'</span>';
        $out .= '<span class="operator">: </span>';
        $out .= '<span class="number">'.($start < $end ? $start.'-'.$end : $start).'</span>';
        $out .= '</span>';

        // Входные параметры
        $out .= self::renderVariable($reflection->getParameters(), 'parameters', $dumper);

        // Статические переменные
        $out .= self::renderVariable($reflection->getStaticVariables(), 'use', $dumper);

        // Возвращаемый тип
        if ($type = $reflection->getReturnType()) {
            $out .= '<span class="row">';
            $out .= '<span class="property">return</span>';
            $out .= '<span class="operator">: </span>';
            $out .= '<span class="type">'.$type.'</span>';
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

            $out .= '<span class="row">';
            $out .= '<span class="block">';
            $out .= '<span class="property">'.$type.'</span>';
            $out .= '<span class="operator">: </span>';
            $out .= '<span class="br-'.$uid.' braces">{</span>';
            $out .= '<a class="to-'.$uid.' toggle" title="Развернуть">>></a>';
            $out .= '<span class="content">';

            switch ($type) {
                case 'parameters':
                    foreach ($vars as $param) {
                        $out .= '<span class="row">';
                        $pType = ($pType = $param->getType()) ? $pType->getName() : '';
                        $out .= '<span class="property" title="'.$pType.'">$'.$param->getName().'</span>';

                        if ($param->isDefaultValueAvailable()) {
                            $out .= '<span class="operator"> = </span>';
                            $out .= $dumper->resolve($param->getDefaultValue());
                        }

                        $out .= '</span>';
                    }
                    break;
                case 'use':
                    foreach ($vars as $key => $value) {
                        $out .= '<span class="row">';
                        $out .= '<span class="property">$'.$key.'</span>';
                        $out .= '<span class="operator"> = </span>';
                        $out .= $dumper->resolve($value);
                        $out .= '</span>';
                    }
                    break;
            }

            $out .= '</span>';
            $out .= '<span class="br-'.$uid.' braces">}</span>';
            $out .= '</span>';
            $out .= '</span>';
        }

        return $out;
    }
}
