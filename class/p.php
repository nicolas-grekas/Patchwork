<?php // vi: set encoding=utf-8 expandtab shiftwidth=4:

/**/if (DEBUG)
/**/{
        class p extends Patchwork
        {
            static function __constructStatic()
            {
                trigger_error("Using class `p' for class `Patchwork' without declaring the alias with `use Patchwork as p;' is deprecated", E_USER_DEPRECATED);
            }
        }
/**/}
/**/else
/**/{
        class p extends Patchwork
        {
        }
/**/}
