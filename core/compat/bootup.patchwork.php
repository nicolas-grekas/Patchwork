<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork\PHP\Override as o;

/**/if (PHP_VERSION_ID < 50300)
/**/{
/**/    // Overrides to backport namespaces to PHP pre-5.3

/**/    boot::$manager->pushFile('class/Patchwork/PHP/Override/Php530.php');

        Patchwork\FunctionOverride(class_implements,        o\Php530, $class, $autoload = true);
        Patchwork\FunctionOverride(class_parents,           o\Php530, $class, $autoload = true);
        Patchwork\FunctionOverride(class_exists,            o\Php530, $class, $autoload = true);
        Patchwork\FunctionOverride(get_class_methods,       o\Php530, $class);
        Patchwork\FunctionOverride(get_class_vars,          o\Php530, $class);
        Patchwork\FunctionOverride(get_class,               o\Php530, $obj);
        Patchwork\FunctionOverride(get_declared_classes,    o\Php530);
        Patchwork\FunctionOverride(get_declared_interfaces, o\Php530);
//        Patchwork\FunctionOverride(get_parent_class,        o\Php530, $class); // FIXME: this is done at superloader level, but this is bad
        Patchwork\FunctionOverride(interface_exists,        o\Php530, $class, $autoload = true);
        Patchwork\FunctionOverride(is_a,                    o\Php530, $obj, $class, $allow_string = false);
        Patchwork\FunctionOverride(is_subclass_of,          o\Php530, $obj, $class, $allow_string = true);
        Patchwork\FunctionOverride(method_exists,           o\Php530, $class, $method);
        Patchwork\FunctionOverride(property_exists,         o\Php530, $class, $property);
        Patchwork\FunctionOverride(lcfirst,                 o\Php530, $str);
/**/}
/**/else if (PHP_VERSION_ID < 50309)
/**/{
        Patchwork\FunctionOverride(is_a,           o\Php539, $obj, $class, $allow_string = false);
        Patchwork\FunctionOverride(is_subclass_of, o\Php539, $obj, $class, $allow_string = true);
/**/}

/**/if (!function_exists('trait_exists'))
/**/{
        function trait_exists($class, $autoload = true) {return $autoload && class_exists($class, $autoload) && false;}
/**/}

/**/if (!function_exists('spl_object_hash'))
/**/{
        Patchwork\FunctionOverride(spl_object_hash, o\Php530, $object);
/**/}

/**/if (PHP_VERSION_ID == 50210 || PHP_VERSION_ID == 502011 || PHP_VERSION_ID == 50300)
/**/{
        Patchwork\FunctionOverride(stream_socket_client, o\Bug48805, $remote_socket, &$errno = null, &$errstr = null, $timeout = null, $flags = STREAM_CLIENT_CONNECT, $context = null);
        Patchwork\FunctionOverride(fsockopen,            o\Bug48805, $hostname, $port = -1, &$errno = null, &$errstr = null, $timeout = null);
/**/}

/**/if (PHP_VERSION_ID == 50209)
/**/{
/**/    // Fix 5.2.9 array_unique() default sort flag
        Patchwork\FunctionOverride(array_unique, array_unique, $array, $sort_flags = SORT_STRING);
/**/}

// Backport UTF-8 default charset from PHP 5.4.0, add new $double_encode parameter (since 5.2.3)

/**/if (PHP_VERSION_ID < 50400)
/**/{
        Patchwork\FunctionOverride(html_entity_decode, html_entity_decode, $s, $style = ENT_COMPAT, $charset = 'UTF-8');
        Patchwork\FunctionOverride(get_html_translation_table, get_html_translation_table, $table = HTML_SPECIALCHARS, $style = ENT_COMPAT, $charset = 'UTF-8');

/**/    if (PHP_VERSION_ID < 50203)
/**/    {
/**/        boot::$manager->pushFile('class/Patchwork/PHP/Override/Php523.php');

            Patchwork\FunctionOverride(htmlspecialchars, o\Php523, $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
            Patchwork\FunctionOverride(htmlentities,     o\Php523, $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
/**/    }
        Patchwork\FunctionOverride(htmlspecialchars, htmlspecialchars, $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
        Patchwork\FunctionOverride(htmlentities,     htmlentities,     $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
/**/}

/**/if (!function_exists('memory_get_usage'))
/**/{
        function memory_get_usage($real = false) {return 0;}
/**/}

/**/if (!function_exists('memory_get_peak_usage'))
/**/{
        function memory_get_peak_usage($real = false) {return 0;}
/**/}

// Workaround mbstring function overloading

/**/if (extension_loaded('mbstring'))
/**/{
/**/    if (MB_OVERLOAD_MAIL & (int) ini_get('mbstring.func_overload'))
/**/    {
            Patchwork\FunctionOverride(mail, o\Mbstring8bit, $to, $subject, $message, $headers = '', $params = '');
/**/    }

/**/    if (MB_OVERLOAD_STRING & (int) ini_get('mbstring.func_overload'))
/**/    {
/**/        boot::$manager->pushFile('class/Patchwork/PHP/Override/Mbstring8bit.php');

            Patchwork\FunctionOverride(strlen,   o\Mbstring8bit, $s);
            Patchwork\FunctionOverride(strpos,   o\Mbstring8bit, $s, $needle, $offset = 0);
            Patchwork\FunctionOverride(strrpos,  o\Mbstring8bit, $s, $needle, $offset = 0);
            Patchwork\FunctionOverride(substr,   o\Mbstring8bit, $s, $start, $length = 2147483647);
            Patchwork\FunctionOverride(stripos,  o\Mbstring8bit, $s, $needle, $offset = 0);
            Patchwork\FunctionOverride(stristr,  o\Mbstring8bit, $s, $needle, $part = false);
            Patchwork\FunctionOverride(strrchr,  o\Mbstring8bit, $s, $needle, $part = false);
            Patchwork\FunctionOverride(strripos, o\Mbstring8bit, $s, $needle, $offset = 0);
            Patchwork\FunctionOverride(strstr,   o\Mbstring8bit, $s, $needle, $part = false);
/**/    }
/**/}

// Default serialize precision is 100, but 17 is enough

/**/if (17 != ini_get('serialize_precision'))
        ini_set('serialize_precision', 17);

// Workaround ob_gzhandler non-discardability

/**/if (PHP_VERSION_ID >= 50400)
        Patchwork\FunctionOverride(ob_gzhandler, ob_gzhandler, $buffer , $mode);
