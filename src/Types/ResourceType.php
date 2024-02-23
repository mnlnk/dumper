<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

use Manuylenko\Dumper\Types\Resources\StreamResource;

class ResourceType extends Type
{
    /**
     * Рендерит ресурс.
     *
     * @param resource $resource
     */
    public function render($resource): string
    {
        $out = '';

        $type = get_resource_type($resource);
        $uId = $this->dumper->genUId();

        $out .= '<span class="md-object md-block">';
        $out .= '<span class="md-resource" title="resource">Resource </span>';
        $out .= '<span class="md-operator">: </span>';
        $out .= '<span class="md-type">'.$type.'</span> ';
        $out .= '<span class="md-br-'.$uId.' md-braces" title="resource">{</span>';

        $data = match ($type) {
            'stream' => (new StreamResource())->getData($resource),
            default => []
        };

        if (count($data) > 0) {
            $out .= '<a class="md-to-'.$uId.' md-toggle" title="Expand">>></a>';
            $out .= '<span class="md-content">';

            foreach ($data as $key => $value) {
                $out .= '<span class="md-row">';
                $out .= '<span class="md-property">'.$key.'</span>';
                $out .= '<span class="md-operator">: </span>';
                $out .= $this->dumper->resolve($value);
                $out .= '</span>';
            }

            $out .= '</span>';
        }

        $out .= '<span class="md-br-'.$uId.' md-braces" title="resource">}</span>';
        $out .= '</span>';

        return $out;
    }
}
