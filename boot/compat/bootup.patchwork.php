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


// Overrides to backport namespaces to PHP pre-5.3

/**/if (PHP_VERSION_ID < 50300)
/**/{
/**/    /*<*/boot::$manager->override('class_implements',        ':Class:', array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/boot::$manager->override('class_parents',           ':Class:', array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/boot::$manager->override('class_exists',            ':Class:', array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/boot::$manager->override('get_class_methods',       ':Class:', array('$class'))/*>*/;
/**/    /*<*/boot::$manager->override('get_class_vars',          ':Class:', array('$class'))/*>*/;
/**/    /*<*/boot::$manager->override('get_class',               ':Class:', array('$obj'))/*>*/;
/**/    /*<*/boot::$manager->override('get_declared_classes',    ':Class:', array())/*>*/;
/**/    /*<*/boot::$manager->override('get_declared_interfaces', ':Class:', array())/*>*/;
/**/    /*<*/boot::$manager->override('get_parent_class',        ':Class:', array('$class'))/*>*/;
/**/    /*<*/boot::$manager->override('interface_exists',        ':Class:', array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/boot::$manager->override('is_a',                    ':Class:', array('$obj', '$class'))/*>*/;
/**/    /*<*/boot::$manager->override('is_subclass_of',          ':Class:', array('$obj', '$class'))/*>*/;
/**/    /*<*/boot::$manager->override('method_exists',           ':Class:', array('$class', '$method'))/*>*/;
/**/    /*<*/boot::$manager->override('property_exists',         ':Class:', array('$class', '$property'))/*>*/;
/**/}

/**/if (!function_exists('spl_object_hash'))
/**/{
/**/    /*<*/boot::$manager->override('spl_object_hash',   ':Class:', array('$object'))/*>*/;
/**/}


// Replace file_exists() on Windows to fix a bug with long file names

/**/if ('\\' === DIRECTORY_SEPARATOR && PHP_VERSION_ID < 50200)
/**/{
/**/    /*<*/boot::$manager->override('file_exists',   ':Winfs:', array('$file'))/*>*/;
/**/    /*<*/boot::$manager->override('is_file',       ':Winfs:', array('$file'))/*>*/;
/**/    /*<*/boot::$manager->override('is_dir',        ':Winfs:', array('$file'))/*>*/;
/**/    /*<*/boot::$manager->override('is_link',       ':Winfs:', array('$file'))/*>*/;
/**/    /*<*/boot::$manager->override('is_executable', ':Winfs:', array('$file'))/*>*/;
/**/    /*<*/boot::$manager->override('is_readable',   ':Winfs:', array('$file'))/*>*/;
/**/    /*<*/boot::$manager->override('is_writable',   ':Winfs:', array('$file'))/*>*/;
/**/}


/**/ // Fix 5.2.9 array_unique() default sort flag
/**/if (PHP_VERSION_ID == 50209)
/**/    /*<*/boot::$manager->override('array_unique', 'array_unique', array('$array', '$sort_flags' => SORT_STRING))/*>*/;

/**/if (PHP_VERSION_ID < 50200)
/**/{
/**/    // Workaround http://bugs.php.net/37394
/**/    /*<*/boot::$manager->override('substr_compare', ':520:', array('$main_str', '$str', '$offset', '$length' => INF, '$case_insensitivity' => false))/*>*/;

/**/    // Backport $httpOnly parameter
/**/    $a = array('$name', '$value' => '', '$expires' => 0, '$path' => '', '$domain' => '', '$secure' => false, '$httponly' => false);
/**/    /*<*/boot::$manager->override('setcookie',    ':520:', $a)/*>*/;
/**/    /*<*/boot::$manager->override('setcookieraw', ':520:', $a)/*>*/;
/**/}


// Turn off magic quotes runtime

/**/if (function_exists('get_magic_quotes_runtime') && @get_magic_quotes_runtime())
/**/{
/**/    @set_magic_quotes_runtime(false);
/**/    @get_magic_quotes_runtime()
/**/        && die('Patchwork error: Failed to turn off magic_quotes_runtime');

        set_magic_quotes_runtime(false);
/**/}


// Workaround for http://bugs.php.net/33140

/**/if ('\\' === DIRECTORY_SEPARATOR && PHP_VERSION_ID < 50200)
/**/{
/**/    /*<*/boot::$manager->override('mkdir', 'patchwork_mkdir', array('$pathname', '$mode' => 0777, '$recursive' => false, '$context' => INF))/*>*/;

        function patchwork_mkdir($pathname, $mode = 0777, $recursive = false, $context = INF)
        {
            return INF === $context
                ? mkdir(strtr($pathname, '/', '\\'), $mode, $recursive)
                : mkdir($pathname, $mode, $recursive, $context);
        }
/**/}


// Default serialize precision is 100, but 17 is enough

/**/if (17 != ini_get('serialize_precision'))
        ini_set('serialize_precision', 17);
