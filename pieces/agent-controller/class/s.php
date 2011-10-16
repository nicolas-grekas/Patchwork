<?php // vi: set fenc=utf-8 ts=4 sw=4 et:

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
