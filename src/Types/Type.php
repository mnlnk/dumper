<?php
declare(strict_types=1);

namespace Manuylenko\Dumper\Types;

abstract class Type
{
    /**
     * ..
     *
     * @var string[]
     */
    protected static array $uid = [];


    /**
     * ..
     */
    protected static function getUid(): string
    {
        while (true) {
            $uid = substr(md5((string) mt_rand(1, 100000)), -4);

            if (! in_array($uid, self::$uid)) {
                self::$uid[] = $uid;

                return $uid;
            }
        }
    }
}
