<?php // vi: set encoding=utf-8 expandtab shiftwidth=4:

class p extends patchwork
{
    static function __init()
    {
        trigger_error("Using class `p' for class `patchwork' without declaring the alias with `use patchwork as p;' is deprecated", E_USER_DEPRECATED);
    }
}
