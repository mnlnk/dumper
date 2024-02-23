<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

use Closure;
use Manuylenko\Dumper\Types\Objects\ClosureObject;
use ReflectionObject;

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

        $out  = '<span class="md-object md-block">';
        $out .= $this->renderClass(get_class($object));
        $out .= ' <span class="md-ha-'.$objId.' md-hash" title="id">#'.$objId.'</span> ';

        if (in_array($object, static::$renderList)) {
            $recId = static::$braces[$objId];

            $out .= '<span class="md-braces" title="object">{</span>';
            $out .= '<span class="md-re-'.$recId.' md-recursion" title="recursion">&recursion</span>';
            $out .= '<span class="md-braces" title="object">}</span>';
        }
        else {
            $uId = $this->dumper->genUId();

            static::$renderList[] = $object;
            static::$braces[$objId] = $uId;

            $out .= '<span class="md-br-'.$uId.' md-braces" title="object">{</span>';
            $out .= $this->renderObject($object, $uId);
            $out .= '<span class="md-br-'.$uId.' md-braces" title="object">}</span>';

            unset(static::$braces[$objId]);
            array_pop(static::$renderList);
        }

        $out .= '</span>';

        return $out;
    }

    /**
     * Рендерит пространство имен и имя класса.
     */
    public function renderClass(string $class): string
    {
        $out = '';

        $separator = mb_strrpos($class, '\\');

        if ($separator > 0) {
            $namespace = mb_substr($class, 0, $separator);
            $class = mb_substr($class, $separator + 1);

            $out .= '<span class="md-namespace" title="namespace"';

            if ($this->shortNamespaceLength < mb_strlen($namespace) - 3) {
                $shortNamespace = mb_substr($namespace, 0, $this->shortNamespaceLength);

                $out .= ' data-ns="'.$namespace.'\\">'.$shortNamespace.'...';
            }
            else {
                $out .= '>'.$namespace;
            }

            $out .= '\\</span>';
        }

        $out .= '<span class="md-class" title="class">'.$class.'</span>';

        return $out;
    }

    /**
     * Рендерит содержимое объекта.
     */
    protected function renderObject(object $object, string $uId): string
    {
        if ($object instanceof Closure) {
            return (new ClosureObject($this))->render($object, $uId);
        }

        $out = '';

        $props = (new ReflectionObject($object))->getProperties();

        if (count($props) > 0) {
            $out .= '<a class="md-to-'.$uId.' md-toggle" title="Expand">>></a>';
            $out .= '<span class="md-content">';

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

                $out .= '<span class="md-row">';

                if ($prop->isStatic()) {
                    $out .= '<span class="md-modifier md-static" title="'.$title.' static">('.$modifier.')</span>';
                }
                else {
                    $out .= '<span class="md-modifier" title="'.$title.'">';
                    $out .= '<span class="md-parentheses">(</span>';
                    $out .= $modifier;
                    $out .= '<span class="md-parentheses">)</span>';
                    $out .= '</span>';
                }

                $out .= ' ';
                $out .= '<span class="md-property">$'.$prop->getName().'</span>';
                $out .= '<span class="md-operator"> = </span>';

                if ($prop->isInitialized($object)) {
                    $out .= $this->dumper->resolve($prop->getValue($object));
                }
                else {
                    $out .= '<span class="md-not-init" title="uninitialized">#E#</span>';
                }

                $out .= '</span>';
            }

            $out .= '</span>';
        }

        return $out;
    }
}
