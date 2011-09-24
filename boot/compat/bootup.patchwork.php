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
/**/    /*<*/boot::$manager->override('class_implements',        ':530:', array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/boot::$manager->override('class_parents',           ':530:', array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/boot::$manager->override('class_exists',            ':530:', array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/boot::$manager->override('get_class_methods',       ':530:', array('$class'))/*>*/;
/**/    /*<*/boot::$manager->override('get_class_vars',          ':530:', array('$class'))/*>*/;
/**/    /*<*/boot::$manager->override('get_class',               ':530:', array('$obj'))/*>*/;
/**/    /*<*/boot::$manager->override('get_declared_classes',    ':530:', array())/*>*/;
/**/    /*<*/boot::$manager->override('get_declared_interfaces', ':530:', array())/*>*/;
/**/    /*<*/boot::$manager->override('get_parent_class',        ':530:', array('$class'))/*>*/;
/**/    /*<*/boot::$manager->override('interface_exists',        ':530:', array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/boot::$manager->override('is_a',                    ':530:', array('$obj', '$class', '$allow_string' => false))/*>*/;
/**/    /*<*/boot::$manager->override('is_subclass_of',          ':530:', array('$obj', '$class', '$allow_string' => true))/*>*/;
/**/    /*<*/boot::$manager->override('method_exists',           ':530:', array('$class', '$method'))/*>*/;
/**/    /*<*/boot::$manager->override('property_exists',         ':530:', array('$class', '$property'))/*>*/;
/**/}
/**/else if (PHP_VERSION_ID < 50309)
/**/{
/**/    /*<*/boot::$manager->override('is_a',           ':539:', array('$obj', '$class', '$allow_string' => false))/*>*/;
/**/    /*<*/boot::$manager->override('is_subclass_of', ':539:', array('$obj', '$class', '$allow_string' => true))/*>*/;
/**/}

/**/if (!function_exists('spl_object_hash'))
/**/{
/**/    /*<*/boot::$manager->override('spl_object_hash', ':530:', array('$object'))/*>*/;
/**/}


/**/ // Fix 5.2.9 array_unique() default sort flag
/**/if (PHP_VERSION_ID == 50209)
/**/    /*<*/boot::$manager->override('array_unique', 'array_unique', array('$array', '$sort_flags' => SORT_STRING))/*>*/;


// Default serialize precision is 100, but 17 is enough

/**/if (17 != ini_get('serialize_precision'))
        ini_set('serialize_precision', 17);
