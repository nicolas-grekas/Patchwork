<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

// Code authored by Anthony Ferrara <ircmaxell@gmail.com>
// See https://github.com/ircmaxell/password_compat/

namespace Patchwork\PHP\Override;

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
        if ($algo && !function_exists('crypt')) {
            trigger_error("Crypt must be loaded for password_hash to function", E_USER_WARNING);
            return null;
        }
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
                if (0 !== $algo) {
                    $required_salt_len = 22;
                    $hash_format = sprintf(/*<*/PHP_VERSION_ID >= 50307 ? "$2y$%02d$" : "$2a$%02d$"/*>*/, $cost);
                    break;
                } elseif (0 === PASSWORD_DEFAULT) {
                    $crypt = __CLASS__ . '::crypt_md5';
                    $required_salt_len = 8;
                    $hash_format = '$P$' . self::$itoa64[min($cost + 5, 30)];
                    break;
                }
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
            $salt = self::password_make_salt($required_salt_len);
        }
        $salt = substr($salt, 0, $required_salt_len);

        $hash = $hash_format . $salt;

        $ret = isset($crypt) ? call_user_func($crypt, $password, $hash) : crypt($password, $hash);

        if (!is_string($ret) || strlen($ret) < 13) {
            return false;
        }

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
            if (7 <= $hash && $hash <= 30)
            {
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
                if (PHP_VERSION_ID >= 50307 && 'bcrypt' !== $info['algoName']) {
                    return true;
                }
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

        if ('$P$' === $ret || '$H$' === $ret) {
            $ret = self::crypt_md5($password, $hash);
        } else {
/**/        if (!function_exists('crypt')) {
                trigger_error("Crypt must be loaded for password_verify to function", E_USER_WARNING);
                return false;
/**/        }
            $ret = crypt($password, $hash);
        }

        if (!is_string($ret) || strlen($ret) != strlen($hash)) {
            return false;
        }

        $status = 0;
        $i = strlen($ret);
        while ($i-- > 0) {
            $status |= (ord($ret[$i]) ^ ord($hash[$i]));
        }

        return $status === 0;
    }

    /**
     * Function to make a salt
     *
     * @internal
     */
    protected static function password_make_salt($length) {
        if ($length <= 0) {
            trigger_error(sprintf("Length cannot be less than or equal zero: %d", $length), E_USER_WARNING);
            return false;
        }
        $buffer = '';
        $raw_length = (int) ($length * 3 / 4 + 1);
        $buffer_valid = false;
        if (function_exists('mcrypt_create_iv')) {
            $buffer = mcrypt_create_iv($raw_length, MCRYPT_DEV_URANDOM);
            if ($buffer) {
                $buffer_valid = true;
            }
        }
        if (!$buffer_valid && function_exists('openssl_random_pseudo_bytes')) {
            $buffer = openssl_random_pseudo_bytes($raw_length);
            if ($buffer) {
                $buffer_valid = true;
            }
        }
        if (!$buffer_valid && file_exists('/dev/urandom')) {
            $f = @fopen('/dev/urandom', 'r');
            if ($f) {
                $read = strlen($buffer);
                while ($read < $raw_length) {
                    $buffer .= fread($f, $raw_length - $read);
                    $read = strlen($buffer);
                }
                fclose($f);
                if ($read >= $raw_length) {
                    $buffer_valid = true;
                }
            }
        }
        if (!$buffer_valid) {
            for ($i = 0; $i < $raw_length; $i++) {
                $buffer .= chr(mt_rand(0, 255));
            }
        }
        $buffer = str_replace('+', '.', base64_encode($buffer));
        return substr($buffer, 0, $length);
    }

    protected static $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Hash the password using PHPass' MD5 portable scheme
     *
     * Implementation borrowed from http://www.openwall.com/phpass/
     *
     * @param string $password The password to hash
     * @param int    $salt     The salt to use
     *
     * @returns string|false The hashed password, or false on error.
     */
    protected static function crypt_md5($password, $salt)
    {
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
