<?php

use Manuylenko\Dumper\Dumper;


if (! function_exists('dump')) {
    /**
     * @return void
     */
    function dump() {
        foreach (func_get_args() as $var) {
            (new Dumper())->dump($var);
        }
    }
}

if (! function_exists('dumpEx')) {
    /**
     * @return void
     */
    function dumpEx() {
        call_user_func_array('dump', func_get_args());
        exit;
    }
}
