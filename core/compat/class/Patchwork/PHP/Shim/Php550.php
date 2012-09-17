<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 *
 * This file incorporates work covered by the MIT licence,
 * by Anthony Ferrara <ircmaxell@gmail.com>
 */

namespace Patchwork\PHP\Shim;

class Php550
{
    /**
     * Hash the password using the specified algorithm
     *
     * @param string $password The password to hash
     * @param int    $algo     The algorithm to use (Defined by PASSWORD_* constants)
     * @param array  $options  The options for the algorithm to use
     *
     * @returns string|false The hashed password, or false on error.
     */
    static function password_hash($password, $algo, $options = array()) {
/**/    if (!function_exists('crypt') || 0 === PASSWORD_DEFAULT) {
            if ($algo) {
                trigger_error("Crypt must be loaded for password_hash to function", E_USER_WARNING);
                return null;
            }
/**/    }
        if (!is_string($password)) {
            trigger_error("password_hash(): Password must be a string", E_USER_WARNING);
            return null;
        }
        if (!is_int($algo)) {
            trigger_error("password_hash() expects parameter 2 to be long, " . gettype($algo) . " given", E_USER_WARNING);
            return null;
        }
        switch ($algo) {
            case 0:
            case PASSWORD_BCRYPT:
                // Note that this is a C constant, but not exposed to PHP, so we don't define it here.
                $cost = 10;
                if (isset($options['cost'])) {
                    $cost = $options['cost'];
                    if ($cost < 4 || $cost > 31) {
                        trigger_error(sprintf("password_hash(): Invalid cost parameter specified: %d", $cost), E_USER_WARNING);
                        return null;
                    }
                }
/**/            if (0 === PASSWORD_DEFAULT) {
                    $crypt = __CLASS__ . '::crypt_md5';
                    $required_salt_len = 8;
                    $hash_format = '$P$' . self::$itoa64[min($cost + 5, 30)];
                    break;
/**/            } else {
                    $required_salt_len = 22;
                    $hash_format = sprintf(/*<*/PHP_VERSION_ID >= 50307 ? "$2y$%02d$" : "$2a$%02d$"/*>*/, $cost);
                    break;
/**/            }
            default:
                trigger_error(sprintf("password_hash(): Unknown password hashing algorithm: %s", $algo), E_USER_WARNING);
                return null;
        }
        if (isset($options['salt'])) {
            switch (gettype($options['salt'])) {
                case 'NULL':
                case 'boolean':
                case 'integer':
                case 'double':
                case 'string':
                    $salt = (string) $options['salt'];
                    break;
                case 'object':
                    if (method_exists($options['salt'], '__tostring')) {
                        $salt = (string) $options['salt'];
                        break;
                    }
                case 'array':
                case 'resource':
                default:
                    trigger_error('password_hash(): Non-string salt parameter supplied', E_USER_WARNING);
                    return null;
            }
            if (strlen($salt) < $required_salt_len) {
                trigger_error(sprintf("password_hash(): Provided salt is too short: %d expecting %d", strlen($salt), $required_salt_len), E_USER_WARNING);
                return null;
            } elseif (0 == preg_match('#^[a-zA-Z0-9./]+$#D', $salt)) {
                $salt = str_replace('+', '.', base64_encode($salt));
            }
        } else {
            $salt = '';
            $raw_length = (int) (.75 * $required_salt_len);
            $raw_length += 4 - $raw_length % 4;
/**/        if (function_exists('mcrypt_create_iv')) {
                $salt = mcrypt_create_iv($raw_length, MCRYPT_DEV_URANDOM);
/**/        }
/**/        if (function_exists('openssl_random_pseudo_bytes')) {
                $salt or $salt = openssl_random_pseudo_bytes($raw_length);
/**/        }
/**/        if (@file_exists('/dev/urandom')) {
                $salt or $salt = @file_get_contents('/dev/urandom', false, null, -1, $raw_length);
/**/        }
            if (0 < $raw_length -= strlen($salt)) {
                $salt .= str_repeat(' ', $raw_length);
                $i = 0;
                while ($i < $raw_length) {
                    $ret = pack('L', mt_rand());
                    $salt[$i++] ^= $ret[0];
                    $salt[$i++] ^= $ret[1];
                    $salt[$i++] ^= $ret[2];
                    $salt[$i++] ^= $ret[3];
                }
            }
            $salt = str_replace('+', '.', base64_encode($salt));
        }
        $salt = substr($salt, 0, $required_salt_len);

        $ret = isset($crypt)
            ? call_user_func($crypt, $password, $hash_format . $salt)
            : crypt($password, $hash_format . $salt);

        if (!is_string($ret) || strlen($ret) <= 13) {
            return false;
        }

/**/    if (PHP_VERSION_ID < 50307 && PASSWORD_DEFAULT)
            $ret[2] = 'x';

        return $ret;
    }

    /**
     * Get information about the password hash. Returns an array of the information
     * that was used to generate the password hash.
     *
     * array(
     *    'algo' => 1,
     *    'algoName' => 'bcrypt',
     *    'options' => array(
     *        'cost' => 10,
     *    ),
     * )
     *
     * @param string $hash The password hash to extract info from
     *
     * @return array The array of information about the hash.
     */
    static function password_get_info($hash) {
        $return = array(
            'algo' => 0,
            'algoName' => 'unknown',
            'options' => array(),
        );
        if (strlen($hash) === 60 && preg_match('#^\$2([axy])\$(\d+)\$#', $hash, $hash)) {
            $return['algo'] = PASSWORD_BCRYPT;
            $return['algoName'] = ltrim($hash[1] . 'bcrypt', 'y');
            $return['options']['cost'] = $hash[2];
        } elseif (strlen($hash) === 34 && preg_match('#^\$[PM]\$(.)#', $hash, $hash)) {
            $hash = strpos(self::$itoa64, $hash[1]);
            if (7 <= $hash && $hash <= 30) {
                $return['algoName'] = 'phpass-md5';
                $return['options']['cost'] = $hash - 5;
            }
        }
        return $return;
    }

    /**
     * Determine if the password hash needs to be rehashed according to the options provided
     *
     * If the answer is true, after validating the password using password_verify, rehash it.
     *
     * @param string $hash    The hash to test
     * @param int    $algo    The algorithm used for new password hashes
     * @param array  $options The options array passed to password_hash
     *
     * @return boolean True if the password needs to be rehashed.
     */
    static function password_needs_rehash($hash, $algo, array $options = array()) {
        $info = self::password_get_info($hash);
        if ($info['algo'] != $algo) {
            return true;
        }
        switch ($algo) {
            case PASSWORD_BCRYPT:
/**/            if (PHP_VERSION_ID >= 50307)
                    if ('bcrypt' !== $info['algoName'])
                        return true;
            case 0:
                $cost = isset($options['cost']) ? $options['cost'] : 10;
                if (isset($info['options']['cost']) && $cost != $info['options']['cost']) {
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * Verify a password against a hash using a timing attack resistant approach
     *
     * @param string $password The password to verify
     * @param string $hash     The hash to verify against
     *
     * @return boolean If the password matches the hash
     */
    static function password_verify($password, $hash) {
        $ret = substr($hash, 0, 3);
        if (!isset($hash[12])) return false;

        if ('$P$' === $ret || '$H$' === $ret) {
            $ret = self::crypt_md5($password, $hash);
        } else {
/**/        if (!function_exists('crypt')) {
                trigger_error("Crypt must be loaded for password_verify to function", E_USER_WARNING);
                return false;
/**/        }

/**/        if (PHP_VERSION_ID < 50307 && PASSWORD_DEFAULT)
                if ('$' === $hash[3] && ('$2x' === $ret || '$2y' === $ret)) $hash[2] = 'a';

            $ret = crypt($password, $hash);
        }

        if (!is_string($ret) || strlen($ret) != strlen($hash) || strlen($ret) <= 13) {
            return false;
        }

        $status = 0;
        $i = strlen($ret);
        while ($i-- > 0) {
            $status |= (ord($ret[$i]) ^ ord($hash[$i]));
        }

        return $status === 0;
    }


    protected static $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Hash the password using PHPass' MD5 portable scheme
     *
     * @param string $password The password to hash
     * @param int    $salt     The salt to use
     *
     * @returns string|false The hashed password, or false on error.
     */
    protected static function crypt_md5($password, $salt) {
        $salt = substr($salt, 0, 12);
        if (!isset($salt[11])) return false;

        $cost = substr($salt, 0, 3);
        if ($cost !== '$P$' && $cost !== '$H$') return false;

        $cost = strpos(self::$itoa64, $salt[3]);
        if ($cost < 7 || $cost > 30) return false;

        $cost = 1 << $cost;

        $hash = md5(substr($salt, 4, 8) . $password, true);
        do $hash = md5($hash . $password, true);
        while (--$cost);

        do {
            $v = ord($hash[$cost++]);
            $salt .= self::$itoa64[$v & 0x3F];
            if ($cost < 16) $v |= ord($hash[$cost]) << 8;
            $salt .= self::$itoa64[($v >> 6) & 0x3F];
            if ($cost++ >= 16) break;
            if ($cost < 16) $v |= ord($hash[$cost]) << 16;
            $salt .= self::$itoa64[($v >> 12) & 0x3F];
            if ($cost++ >= 16) break;
            $salt .= self::$itoa64[($v >> 18) & 0x3F];
        } while ($cost < 16);

        return $salt;
    }
}
