<?php
declare(strict_types=1);

use Manuylenko\Dumper\Dumper;

if (! function_exists('dump')) {
    /**
     * ..
     */
    function dump(): void {
        foreach (func_get_args() as $var) {
            (new Dumper())->dump($var);
        }
    }
}

if (! function_exists('dumpEx')) {
    /**
     * ..
     */
    function dumpEx(): never {
        call_user_func_array('dump', func_get_args());
        exit;
    }
}
