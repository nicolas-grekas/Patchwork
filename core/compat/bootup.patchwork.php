<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork\PHP\Shim as s;

/**/if (PHP_VERSION_ID < 50300)
/**/{
/**/    // Shims to backport namespaces to PHP pre-5.3

/**/    boot::$manager->pushFile('class/Patchwork/PHP/Shim/Php530.php');

        Patchwork\Shim(class_implements,        s\Php530, $class, $autoload = true);
        Patchwork\Shim(class_parents,           s\Php530, $class, $autoload = true);
        Patchwork\Shim(class_exists,            s\Php530, $class, $autoload = true);
        Patchwork\Shim(get_class_methods,       s\Php530, $class);
        Patchwork\Shim(get_class_vars,          s\Php530, $class);
        Patchwork\Shim(get_class,               s\Php530, $obj);
        Patchwork\Shim(get_declared_classes,    s\Php530);
        Patchwork\Shim(get_declared_interfaces, s\Php530);
//        Patchwork\Shim(get_parent_class,        s\Php530, $class); // FIXME: this is done at superloader level, but this is bad
        Patchwork\Shim(interface_exists,        s\Php530, $class, $autoload = true);
        Patchwork\Shim(is_a,                    s\Php530, $obj, $class, $allow_string = false);
        Patchwork\Shim(is_subclass_of,          s\Php530, $obj, $class, $allow_string = true);
        Patchwork\Shim(lcfirst,                 s\Php530, $str);
        Patchwork\Shim(method_exists,           s\Php530, $class, $method);
        Patchwork\Shim(property_exists,         s\Php530, $class, $property);
        Patchwork\Shim(spl_object_hash,         s\Php530, $object);
/**/}
/**/else if (PHP_VERSION_ID < 50309)
/**/{
        Patchwork\Shim(is_a,           s\Php539, $obj, $class, $allow_string = false);
        Patchwork\Shim(is_subclass_of, s\Php539, $obj, $class, $allow_string = true);
/**/}

/**/if (PHP_VERSION_ID < 50302)
/**/{
/**/    boot::$manager->pushFile('class/Patchwork/PHP/Shim/Php532.php');

        Patchwork\Shim(stream_resolve_include_path, s\Php532, $filename);
/**/}

/**/if (!function_exists('trait_exists'))
/**/{
        function trait_exists($class, $autoload = true) {return $autoload && class_exists($class, $autoload) && false;}
/**/}

/**/if (PHP_VERSION_ID == 50210 || PHP_VERSION_ID == 502011 || PHP_VERSION_ID == 50300)
/**/{
        Patchwork\Shim(stream_socket_client, s\Bug48805, $remote_socket, &$errno = null, &$errstr = null, $timeout = null, $flags = STREAM_CLIENT_CONNECT, $context = null);
        Patchwork\Shim(fsockopen,            s\Bug48805, $hostname, $port = -1, &$errno = null, &$errstr = null, $timeout = null);
/**/}

/**/if (PHP_VERSION_ID == 50209)
/**/{
/**/    // Fix 5.2.9 array_unique() default sort flag
        Patchwork\Shim(array_unique, array_unique, $array, $sort_flags = SORT_STRING);
/**/}

// Backport UTF-8 default charset from PHP 5.4.0, add new $double_encode parameter (since 5.2.3)

/**/if (PHP_VERSION_ID < 50400)
/**/{
        Patchwork\Shim(html_entity_decode, html_entity_decode, $s, $style = ENT_COMPAT, $charset = 'UTF-8');
        Patchwork\Shim(get_html_translation_table, get_html_translation_table, $table = HTML_SPECIALCHARS, $style = ENT_COMPAT, $charset = 'UTF-8');

/**/    if (PHP_VERSION_ID < 50203)
/**/    {
/**/        boot::$manager->pushFile('class/Patchwork/PHP/Shim/Php523.php');

            Patchwork\Shim(htmlspecialchars, s\Php523, $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
            Patchwork\Shim(htmlentities,     s\Php523, $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
/**/    }
/**/    else
/**/    {
            Patchwork\Shim(htmlspecialchars, htmlspecialchars, $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
            Patchwork\Shim(htmlentities,     htmlentities,     $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
/**/    }
/**/}

/**/if (PHP_VERSION_ID < 50500)
/**/{
        define('PASSWORD_BCRYPT', 1);
        define('PASSWORD_DEFAULT', /*<*/(int) (function_exists('crypt') && CRYPT_BLOWFISH)/*>*/);

        Patchwork\Shim(password_hash,         s\Php550, $password, $algo, array $options = array());
        Patchwork\Shim(password_get_info,     s\Php550, $hash);
        Patchwork\Shim(password_needs_rehash, s\Php550, $hash, $algo, array $options = array());
        Patchwork\Shim(password_verify,       s\Php550, $password, $hash);
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
            Patchwork\Shim(mail, s\Mbstring8bit, $to, $subject, $message, $headers = '', $params = '');
/**/    }

/**/    if (MB_OVERLOAD_STRING & (int) ini_get('mbstring.func_overload'))
/**/    {
/**/        boot::$manager->pushFile('class/Patchwork/PHP/Shim/Mbstring8bit.php');

            Patchwork\Shim(strlen,   s\Mbstring8bit, $s);
            Patchwork\Shim(strpos,   s\Mbstring8bit, $s, $needle, $offset = 0);
            Patchwork\Shim(strrpos,  s\Mbstring8bit, $s, $needle, $offset = 0);
            Patchwork\Shim(substr,   s\Mbstring8bit, $s, $start, $length = 2147483647);
            Patchwork\Shim(stripos,  s\Mbstring8bit, $s, $needle, $offset = 0);
            Patchwork\Shim(stristr,  s\Mbstring8bit, $s, $needle, $part = false);
            Patchwork\Shim(strrchr,  s\Mbstring8bit, $s, $needle, $part = false);
            Patchwork\Shim(strripos, s\Mbstring8bit, $s, $needle, $offset = 0);
            Patchwork\Shim(strstr,   s\Mbstring8bit, $s, $needle, $part = false);
/**/    }
/**/}

// Default serialize precision is 100, but 17 is enough

/**/if (17 != ini_get('serialize_precision'))
        ini_set('serialize_precision', 17);

// Workaround ob_gzhandler non-discardability in PHP 5.4

/**/if (PHP_VERSION_ID >= 50400)
        Patchwork\Shim(ob_gzhandler, ob_gzhandler, $buffer, $mode);
