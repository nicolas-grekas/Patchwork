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

use Patchwork\PHP\Override as o;

// Overrides to backport namespaces to PHP pre-5.3

/**/if (PHP_VERSION_ID < 50300)
/**/{
        Patchwork\FunctionOverride(class_implements,        o\Php530, $class, $autoload = true);
        Patchwork\FunctionOverride(class_parents,           o\Php530, $class, $autoload = true);
        Patchwork\FunctionOverride(class_exists,            o\Php530, $class, $autoload = true);
        Patchwork\FunctionOverride(get_class_methods,       o\Php530, $class);
        Patchwork\FunctionOverride(get_class_vars,          o\Php530, $class);
        Patchwork\FunctionOverride(get_class,               o\Php530, $obj);
        Patchwork\FunctionOverride(get_declared_classes,    o\Php530);
        Patchwork\FunctionOverride(get_declared_interfaces, o\Php530);
        Patchwork\FunctionOverride(get_parent_class,        o\Php530, $class);
        Patchwork\FunctionOverride(interface_exists,        o\Php530, $class, $autoload = true);
        Patchwork\FunctionOverride(is_a,                    o\Php530, $obj, $class, $allow_string = false);
        Patchwork\FunctionOverride(is_subclass_of,          o\Php530, $obj, $class, $allow_string = true);
        Patchwork\FunctionOverride(method_exists,           o\Php530, $class, $method);
        Patchwork\FunctionOverride(property_exists,         o\Php530, $class, $property);
/**/}
/**/else if (PHP_VERSION_ID < 50309)
/**/{
        Patchwork\FunctionOverride(is_a,           o\Php539, $obj, $class, $allow_string = false);
        Patchwork\FunctionOverride(is_subclass_of, o\Php539, $obj, $class, $allow_string = true);
/**/}

/**/if (!function_exists('trait_exists'))
/**/{
        function trait_exists($class, $autoload = true) {return false;}
/**/}

/**/if (!function_exists('spl_object_hash'))
/**/{
        Patchwork\FunctionOverride(spl_object_hash, o\Php530, $object);
/**/}


/**/ // Fix 5.2.9 array_unique() default sort flag
/**/if (PHP_VERSION_ID == 50209)
        Patchwork\FunctionOverride(array_unique, array_unique, $array, $sort_flags = SORT_STRING);


/**/if (!function_exists('memory_get_usage'))
    function memory_get_usage($real = false) {return 0;}

/**/if (!function_exists('memory_get_peak_usage'))
    function memory_get_peak_usage($real = false) {return 0;}


// Default serialize precision is 100, but 17 is enough

/**/if (17 != ini_get('serialize_precision'))
        ini_set('serialize_precision', 17);
