<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork as p;
use Patchwork\PHP\Shim as s;

/**/if (PHP_VERSION_ID < 50300)
/**/{
/**/    // Shims to backport namespaces to PHP pre-5.3

/**/    boot::$manager->pushFile('class/Patchwork/PHP/Shim/Php530.php');

        p\Shim(class_implements,        s\Php530, $class, $autoload = true);
        p\Shim(class_parents,           s\Php530, $class, $autoload = true);
        p\Shim(class_exists,            s\Php530, $class, $autoload = true);
        p\Shim(get_class_methods,       s\Php530, $class);
        p\Shim(get_class_vars,          s\Php530, $class);
        p\Shim(get_class,               s\Php530, $obj);
        p\Shim(get_declared_classes,    s\Php530);
        p\Shim(get_declared_interfaces, s\Php530);
//        p\Shim(get_parent_class,        s\Php530, $class); // FIXME: this is done at superloader level, but this is bad
        p\Shim(interface_exists,        s\Php530, $class, $autoload = true);
        p\Shim(is_a,                    s\Php530, $obj, $class, $allow_string = false);
        p\Shim(is_subclass_of,          s\Php530, $obj, $class, $allow_string = true);
        p\Shim(lcfirst,                 s\Php530, $str);
        p\Shim(method_exists,           s\Php530, $class, $method);
        p\Shim(property_exists,         s\Php530, $class, $property);
        p\Shim(spl_object_hash,         s\Php530, $object);
/**/}
/**/else if (PHP_VERSION_ID < 50309)
/**/{
/**/    boot::$manager->pushFile('class/Patchwork/PHP/Shim/Php539.php');

        p\Shim(is_a,           s\Php539, $obj, $class, $allow_string = false);
        p\Shim(is_subclass_of, s\Php539, $obj, $class, $allow_string = true);
/**/}

/**/if (PHP_VERSION_ID < 50302)
/**/{
/**/    boot::$manager->pushFile('class/Patchwork/PHP/Shim/Php532.php');

        p\Shim(stream_resolve_include_path, s\Php532, $filename);
/**/}

/**/if (!function_exists('trait_exists'))
/**/{
        function trait_exists($class, $autoload = true) {return $autoload && class_exists($class, $autoload) && false;}
/**/}

/**/if (PHP_VERSION_ID == 50210 || PHP_VERSION_ID == 502011 || PHP_VERSION_ID == 50300)
/**/{
        p\Shim(stream_socket_client, s\Bug48805, $remote_socket, &$errno = null, &$errstr = null, $timeout = null, $flags = STREAM_CLIENT_CONNECT, $context = null);
        p\Shim(fsockopen,            s\Bug48805, $hostname, $port = -1, &$errno = null, &$errstr = null, $timeout = null);
/**/}

/**/if (PHP_VERSION_ID == 50209)
/**/{
/**/    // Fix 5.2.9 array_unique() default sort flag
        p\Shim(array_unique, array_unique, $array, $sort_flags = SORT_STRING);
/**/}

/**/if (PHP_VERSION_ID < 50400)
/**/{
        p\Shim(number_format, s\Php540, $number, $decimals = 0, $dec_point = '.', $thousands_sep = ',');

        // Remove import_request_variables() by mapping it to something non-existant
        p\Shim(import_request_variable, s\Php540, $types, $prefix = '');

        // Backport UTF-8 default charset from PHP 5.4.0, add new $double_encode parameter (since 5.2.3)
        p\Shim(html_entity_decode, html_entity_decode, $s, $style = ENT_COMPAT, $charset = 'UTF-8');
        p\Shim(get_html_translation_table, get_html_translation_table, $table = HTML_SPECIALCHARS, $style = ENT_COMPAT, $charset = 'UTF-8');

/**/    if (PHP_VERSION_ID < 50203)
/**/    {
/**/        boot::$manager->pushFile('class/Patchwork/PHP/Shim/Php523.php');

            p\Shim(htmlspecialchars, s\Php523, $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
            p\Shim(htmlentities,     s\Php523, $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
/**/    }
/**/    else
/**/    {
            p\Shim(htmlspecialchars, htmlspecialchars, $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
            p\Shim(htmlentities,     htmlentities,     $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
/**/    }
/**/}

/**/if (PHP_VERSION_ID < 50500)
/**/{
        p\Shim(array_column, s\Php550, $array, $column_key, $index_key = null);

        const PASSWORD_BCRYPT = 1;
        const PASSWORD_DEFAULT = /*<*/(int) (function_exists('crypt') && CRYPT_BLOWFISH)/*>*/;

        p\Shim(password_hash,         s\Php550, $password, $algo, array $options = array());
        p\Shim(password_get_info,     s\Php550, $hash);
        p\Shim(password_needs_rehash, s\Php550, $hash, $algo, array $options = array());
        p\Shim(password_verify,       s\Php550, $password, $hash);

/**/    if (PHP_VERSION_ID < 50303)
/**/    {
            const JSON_NUMERIC_CHECK = 32;
            const JSON_ERROR_UTF8 = 5;
/**/    }

/**/    if (PHP_VERSION_ID < 50400)
/**/    {
            const JSON_BIGINT_AS_STRING = 2;
            const JSON_UNESCAPED_SLASHES = 64;
            const JSON_PRETTY_PRINT = 128;
            const JSON_UNESCAPED_UNICODE = 256;

            p\Shim(json_decode, s\Php540);
/**/    }

        const JSON_ERROR_RECURSION = 6;
        const JSON_ERROR_INF_OR_NAN = 7;
        const JSON_ERROR_UNSUPPORTED_TYPE = 8;

        p\Shim(json_encode,           s\Php550, $value, $options = 0, $depth = 512);
        p\Shim(json_last_error_msg,   s\Php550);
        p\Shim(set_error_handler,     s\Php550, $error_handler, $error_types = -1);
        p\Shim(set_exception_handler, s\Php550, $exception_handler);
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
            p\Shim(mail, s\Mbstring8bit, $to, $subject, $message, $headers = '', $params = '');
/**/    }

/**/    if (MB_OVERLOAD_STRING & (int) ini_get('mbstring.func_overload'))
/**/    {
/**/        boot::$manager->pushFile('class/Patchwork/PHP/Shim/Mbstring8bit.php');

            p\Shim(strlen,   s\Mbstring8bit, $s);
            p\Shim(strpos,   s\Mbstring8bit, $s, $needle, $offset = 0);
            p\Shim(strrpos,  s\Mbstring8bit, $s, $needle, $offset = 0);
            p\Shim(substr,   s\Mbstring8bit, $s, $start, $length = 2147483647);
            p\Shim(stripos,  s\Mbstring8bit, $s, $needle, $offset = 0);
            p\Shim(stristr,  s\Mbstring8bit, $s, $needle, $part = false);
            p\Shim(strrchr,  s\Mbstring8bit, $s, $needle, $part = false);
            p\Shim(strripos, s\Mbstring8bit, $s, $needle, $offset = 0);
            p\Shim(strstr,   s\Mbstring8bit, $s, $needle, $part = false);
/**/    }
/**/}

// Default serialize precision is 100, but 17 is enough

/**/if (17 != ini_get('serialize_precision'))
        ini_set('serialize_precision', 17);

// Workaround ob_gzhandler non-discardability in PHP 5.4

/**/if (PHP_VERSION_ID >= 50400)
        p\Shim(ob_gzhandler, ob_gzhandler, $buffer, $mode);

// Take entropy from the OS for PHP session ids

/**/if ( !ini_get('session.entropy_file') && !ini_get('session.entropy_length') )
/**/{
        ini_set('session.entropy_length', 32);

/**/    if ( @file_exists('/dev/urandom') && false !== @file_get_contents('/dev/urandom', false, null, -1, 1) )
            ini_set('session.entropy_file', '/dev/urandom');
/**/}
