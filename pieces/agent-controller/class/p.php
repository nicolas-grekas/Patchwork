<?php // vi: set fenc=utf-8 ts=4 sw=4 et:

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
