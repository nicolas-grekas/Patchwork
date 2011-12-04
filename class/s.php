<?php // vi: set encoding=utf-8 expandtab shiftwidth=4:

class s extends SESSION
{
    static function __init()
    {
        trigger_error("Using class `s' for class `SESSION' without declaring the alias with `use SESSION as s;' is deprecated", E_USER_DEPRECATED);
    }
}
