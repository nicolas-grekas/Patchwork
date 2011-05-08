<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


// Backport spl_autoload_register() and related functions from PHP 5.3

@ini_set('unserialize_callback_func', 'spl_autoload_call');

/**/if (function_exists('__autoload'))
/**/{
/**/    if (!function_exists('spl_autoload_register'))
/**/    {
            // "Cannot redeclare" fatal error: autoloading is registered and can't be replaced
            function __autoload($class) {}
/**/    }

        spl_autoload_register('__autoload');
/**/}

/**/if (PHP_VERSION_ID < 50300 || !function_exists('spl_autoload_register'))
/**/{
/**/    // Before PHP 5.3, backport spl_autoload_register()'s $prepend argument
/**/    // and workaround http://bugs.php.net/44144
/**/
/**/    /*<*/boot::$manager->override('__autoload',              ':SplAutoload::spl_autoload_call', array('$class'))/*>*/;
/**/    /*<*/boot::$manager->override('spl_autoload_call',       ':SplAutoload:', array('$class'))/*>*/;
/**/    /*<*/boot::$manager->override('spl_autoload_functions',  ':SplAutoload:', array())/*>*/;
/**/    /*<*/boot::$manager->override('spl_autoload_register',   ':SplAutoload:', array('$callback', '$throw' => true, '$prepend' => false))/*>*/;
/**/    /*<*/boot::$manager->override('spl_autoload_unregister', ':SplAutoload:', array('$callback'))/*>*/;

/**/    boot::$manager->pushFile('class/Patchwork/PHP/Override/SplAutoload.php');
/**/}
/**/else
/**/{
/**/    /*<*/boot::$manager->override('__autoload', 'spl_autoload_call', array('$class'))/*>*/;
/**/}
