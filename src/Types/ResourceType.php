<?php

namespace Manuylenko\Dumper\Types;

use Manuylenko\Dumper\Dumper;

class ResourceType extends Type
{
    /**
     * @param resource $resource
     * @param Dumper $dumper
     *
     * @return string
     */
    public static function render(Dumper $dumper, $resource)
    {
        $out = '';
        $type = get_resource_type($resource);
        $uId = static::getUid();

        $out .= '<span class="md_block md_object">';
        $out .= '<span class="md_resource" title="Ресурс">Resource </span>';
        $out .= '<span class="md_operator">: </span>';
        $out .= '<span class="md_type">'.$type.'</span> ';
        $out .= '<span class="md_br-'.$uId.' md_braces">{</span>';

        $getData = str_replace('-', '', 'get'.ucfirst($type).'Data');

        if (method_exists(__CLASS__, $getData)) {
            $out .= '<a class="md_to-'.$uId.' md_toggle">>></a>';
            $out .= '<span class="md_content">';

            foreach (self::$getData($resource) as $key => $value) {
                $out .= '<span class="md_row">';
                $out .= '<span class="md_property">'.$key.'</span>';
                $out .= '<span class="md_operator">: </span>';
                $out .= '<span class="md_type">'.$dumper->resolve($value).'</span> ';
                $out .= '</span>';
            }

            $out .= '</span>';
        }

        $out .= '<span class="md_br-'.$uId.' md_braces">}</span>';
        $out .= '</span>';

        return $out;
    }

    /**
     * @param resource $stream
     *
     * @return array
     */
    protected static function getStreamData($stream)
    {
        return stream_get_meta_data($stream) + array('context' => self::getStreamContextData($stream));
    }

    /**
     * @param resource $stream
     *
     * @return array
     */
    protected static function getStreamContextData($stream)
    {
        return stream_context_get_params($stream);
    }
}
