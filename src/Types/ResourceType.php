<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

use Manuylenko\Dumper\Dumper;

class ResourceType extends Type
{
    /**
     * Рендерит ресурс.
     *
     * @param resource $resource
     */
    public static function render(Dumper $dumper, $resource): string
    {
        $out = '';

        $type = get_resource_type($resource);
        $uId = Dumper::getUid();

        $out .= '<span class="md_block md_object">';
        $out .= '<span class="md_resource" title="resource">Resource </span>';
        $out .= '<span class="md_operator">: </span>';
        $out .= '<span class="md_type">'.$type.'</span> ';
        $out .= '<span class="md_br-'.$uId.' md_braces" title="resource">{</span>';

        $getDataMethod = str_replace('-', '', 'get'.ucfirst($type).'Data');

        if (method_exists(__CLASS__, $getDataMethod)) {
            $out .= '<a class="md_to-'.$uId.' md_toggle" title="Expand">>></a>';
            $out .= '<span class="md_content">';

            foreach (static::$getDataMethod($resource) as $key => $value) {
                $out .= '<span class="md_row">';
                $out .= '<span class="md_property">'.$key.'</span>';
                $out .= '<span class="md_operator">: </span>';
                $out .= $dumper->resolve($value);
                $out .= '</span>';
            }

            $out .= '</span>';
        }

        $out .= '<span class="md_br-'.$uId.' md_braces" title="resource">}</span>';
        $out .= '</span>';

        return $out;
    }

    /**
     * Получает основную информацию о потоке.
     *
     * @param resource $stream
     */
    protected static function getStreamData($stream): array
    {
        return stream_get_meta_data($stream) + ['context' => stream_context_get_params($stream)];
    }
}
