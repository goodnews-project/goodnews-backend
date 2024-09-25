<?php

namespace App\Util;

class Table
{
    private static $_obj;

    // private Atomic $atomic;

    private function __construct()
    {
        // $this->atomic = new Atomic();
    }

    public static function getInstance()
    {
        if (!self::$_obj instanceof self) {
            self::$_obj = new self();
        }
        return self::$_obj;
    }
}
