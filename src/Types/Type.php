<?php

namespace Manuylenko\Dumper\Types;

abstract class Type
{
    /**
     * @var array
     */
    protected static $uid = [];


    /**
     * @return string
     */
    protected static function getUid()
    {
        while (true) {
            $uid = substr(md5(rand(1, 100000)), -4);

            if (! in_array($uid, self::$uid)) {
                self::$uid[] = $uid;

                return $uid;
            }
        }
    }
}
