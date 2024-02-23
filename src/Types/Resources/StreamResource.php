<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types\Resources;

class StreamResource
{
    /**
     * Основная информация о потоке.
     *
     * @param resource $stream
     */
    public function getData($stream): array
    {
        return [
            // https://www.php.net/manual/ru/function.stream-is-local.php
            'is_local' => stream_is_local($stream),

            // https://www.php.net/manual/ru/function.stream-supports-lock.php
            'supports_lock' => stream_supports_lock($stream),

            // https://www.php.net/manual/ru/function.stream-get-meta-data.php
            'meta' => stream_get_meta_data($stream),

            // https://www.php.net/manual/ru/function.stream-context-get-params.php
            'context' => stream_context_get_params($stream)
        ];
    }
}
