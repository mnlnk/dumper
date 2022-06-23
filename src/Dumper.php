<?php

namespace Manuylenko\Dumper;

use Manuylenko\Dumper\Types\ArrayType;
use Manuylenko\Dumper\Types\BooleanType;
use Manuylenko\Dumper\Types\NullType;
use Manuylenko\Dumper\Types\NumberType;
use Manuylenko\Dumper\Types\ObjectType;
use Manuylenko\Dumper\Types\ResourceType;
use Manuylenko\Dumper\Types\StringType;
use Manuylenko\Dumper\Types\UnknownType;

class Dumper
{
    /**
     * @var array
     */
    protected static $id = [];

    /**
     * @var bool
     */
    protected static $resourcesLoaded = false;


    /**
     * @param mixed $var
     *
     * @return void
     */
    public function dump($var)
    {
        $out = '';

        if (! self::$resourcesLoaded) {
            $css = file_get_contents(__DIR__.'/Resources/Css/light_style.min.css');
            $js = file_get_contents(__DIR__.'/Resources/Js/script.min.js');

            $out .= join(array('<style>', trim($css), '</style>', ''));
            $out .= join(array('<script>', trim($js), '</script>', ''));

            self::$resourcesLoaded = true;
        }

        $id = self::getId();

        $out .= '<div id="id-'.$id.'" class="mnlnk_dump">';
        $out .= '<span class="row">';
        $out .= $this->resolve($var);
        $out .= '</span>';
        $out .= '<script>mnlnkDumpInit("'.$id.'")</script>';
        $out .= '</div>';

        echo $out;
    }

    /**
     * @return string
     */
    public static function getId()
    {
        while (true) {
            $id = substr(md5(rand(1, 100000)), -4);

            if (! in_array($id, self::$id)) {
                self::$id[] = $id;

                return $id;
            }
        }
    }

    /**
     * @param mixed $var
     *
     * @return string
     */
    public function resolve($var)
    {
        switch (strtolower(gettype($var))) {
            case 'null':
                return NullType::render();
            case 'boolean':
                return BooleanType::render($var);
            case 'integer':
            case 'double':
                return NumberType::render($var);
            case 'string':
                return StringType::render($var);
            case 'array':
                return ArrayType::render($this, $var);
            case 'object':
                return ObjectType::render($this, $var);
            case 'resource':
                return ResourceType::render($this, $var);
            default:
                return UnknownType::render();
        }
    }
}
