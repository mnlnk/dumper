<?php
declare(strict_types=1);

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
     * ..
     *
     * @var string[]
     */
    protected static array $uId = [];

    /**
     * ..
     */
    protected static bool $resourcesLoaded = false;


    /**
     * ..
     */
    public function dump(mixed $var): void
    {
        $out = '';

        if (! self::$resourcesLoaded) {
            $css = file_get_contents(__DIR__.'/Resources/Css/light_style.min.css');
            $js = file_get_contents(__DIR__.'/Resources/Js/script.min.js');

            $out .= join(array('<style>', trim($css), '</style>', ''));
            $out .= join(array('<script>', trim($js), '</script>', ''));

            self::$resourcesLoaded = true;
        }

        $id = self::getUId();

        $out .= '<div id="md_id-'.$id.'" class="mnlnk_dump">';
        $out .= '<span class="md_row">';
        $out .= $this->resolve($var);
        $out .= '</span>';
        $out .= '<script>mnlnkDumpInit("'.$id.'")</script>';
        $out .= '</div>';

        echo $out;
    }

    /**
     * ..
     */
    public static function getUId(): string
    {
        while (true) {
            $uId = substr(md5((string) mt_rand(1, 100000)), -4);

            if (! in_array($uId, self::$uId)) {
                self::$uId[] = $uId;

                return $uId;
            }
        }
    }

    /**
     * ..
     */
    public function resolve(mixed$var): string
    {
        return match (strtolower(gettype($var))) {
            'null' => NullType::render(),
            'boolean' => BooleanType::render($var),
            'integer', 'double' => NumberType::render($var),
            'string' => StringType::render($var),
            'array' => ArrayType::render($this, $var),
            'object' => ObjectType::render($this, $var),
            'resource', 'resource (closed)' => ResourceType::render($this, $var),
            default => UnknownType::render(),
        };
    }
}
