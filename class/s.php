<?php // vi: set encoding=utf-8 expandtab shiftwidth=4:

/**/if (DEBUG)
/**/{
        class s extends SESSION
        {
            static function __constructStatic()
            {
                trigger_error("Using class `s' for class `SESSION' without declaring the alias with `use SESSION as s;' is deprecated", E_USER_DEPRECATED);
            }
        }
/**/}
/**/else
/**/{
        class s extends SESSION
        {
        }
/**/}
