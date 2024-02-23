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
     * Массив уникальных идентификаторов.
     *
     * @var string[]
     */
    protected static array $uId = [];

    /**
     * Указывает, что ресурсы (js, сcs) были загружены.
     */
    protected static bool $resourcesLoaded = false;


    /**
     * Выводит дамп данных.
     */
    public function dump(mixed $var): void
    {
        $out = '';

        if (!static::$resourcesLoaded) {
            $css = file_get_contents(__DIR__.'/Resources/Css/light_style.min.css');
            $js = file_get_contents(__DIR__.'/Resources/Js/script.min.js');

            $out .= join(['<style>', trim($css), '</style>', '']);
            $out .= join(['<script>', trim($js), '</script>', '']);

            static::$resourcesLoaded = true;
        }

        $uId = $this->getUId();

        $out .= '<div id="md_id-'.$uId.'" class="mnlnk_dump">';
        $out .= '<span class="md_row">';
        $out .= $this->resolve($var);
        $out .= '</span>';
        $out .= '<script>mnlnkDumpInit("'.$uId.'")</script>';
        $out .= '</div>';

        echo $out;
    }

    /**
     * Получает уникальный идентификатор.
     */
    public function getUId(): string
    {
        while (true) {
            $uId = substr(md5((string) mt_rand(1, 100000)), -4);

            if (!in_array($uId, static::$uId)) {
                static::$uId[] = $uId;

                return $uId;
            }
        }
    }

    /**
     * Решает, как рендерить данные в зависимости от их типа.
     */
    public function resolve(mixed $var): string
    {
        return match (strtolower(gettype($var))) {
            'null' => (new NullType())->render(),
            'boolean' => (new BooleanType())->render($var),
            'integer', 'double' => (new NumberType())->render($var),
            'string' => (new StringType($this))->render($var),
            'array' => (new ArrayType($this))->render($var),
            'object' => (new ObjectType($this))->render($var),
            'resource', 'resource (closed)' => (new ResourceType($this))->render($var),
            default => (new UnknownType())->render()
        };
    }
}
