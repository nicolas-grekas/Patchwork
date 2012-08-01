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

class SESSION
{
    static

    // Session <-> IP binding level (IPv4 only)
    // Not enabled by default because of load-balanced proxy servers,
    // dropped-and-restored dial-up connections, wireless networks, etc.
    $IPlevel = 0,

    $maxIdleTime = 0,
    $maxLifeTime = 43200,

    $gcProbabilityNumerator = 1,
    $gcProbabilityDenominator = 100,

    $authVars = array('user_id'),
    $groupVars = array();


    protected static

    $savePath,
    $cookiePath,
    $cookieDomain,
    $DATA,
    $adapter = false,
    $SID = '',
    $lastseen = '',
    $birthtime = '',
    $sslid = '',
    $isIdled = false,
    $regenerated = false;


    /* Public methods */

    static function getSID()      {p::setPrivate(); return self::$SID;}
    static function getLastseen() {p::setPrivate(); return self::$lastseen;}

    static function get($name)
    {
        $value = isset(self::$DATA[$name]) ? self::$DATA[$name] : '';
        p::setGroup(isset(self::$groupVars[$name]) ? 'session/' . $name . '/' . $value : 'private');
        return $value;
    }

    static function set($name, $value = '')
    {
        $regenerateId = false;

        if (is_array($name) || is_object($name))
        {
            foreach ($name as $k => &$value)
            {
                self::$DATA[$k] =& $value;
                self::$regenerated || $regenerateId || $regenerateId = isset(self::$authVars[$k]);
            }
        }
        else
        {
            if ('' !== $value) self::$DATA[$name] = $value;
            else if (isset(self::$DATA[$name]))
            {
                self::$DATA[$name] = '';
                unset(self::$DATA[$name]);
            }

            self::$regenerated || $regenerateId = isset(self::$authVars[$name]);
        }

        if ($regenerateId)
        {
            self::regenerateId();

/**/        if (DEBUG)
                'POST' === $_SERVER['REQUEST_METHOD'] || user_error("Trying to modify a variable which is member of SESSION::\$authVars during a GET request.");
        }
    }

    static function free($name)
    {
        isset(self::$DATA[$name]) && self::set($name);
    }

    static function bind($name, &$value)
    {
        $value = self::get($name);
        self::set(array($name => &$value));
    }

    static function flash($name, $value = '')
    {
        $a = self::get($name);
        self::set($name, $value);
        return $a;
    }

    static function getAll()
    {
        $a = array();

        foreach (self::$DATA as $k => &$v)
        {
            p::setGroup(isset(self::$groupVars[$k]) ? 'session/' . $k . '/' . $v : 'private');
            $a[$k] =& $v;
        }

        return $a;
    }

    static function regenerateId($initSession = false, $restartNew = true)
    {
        self::$regenerated = true;

        if (self::$adapter)
        {
            self::$adapter->reset();
            self::$adapter = false;
        }

        if ($initSession) self::$DATA = array();

        // Generate a new anti-CSRF token
        p::getAntiCsrfToken(true);

        if (!$initSession || $restartNew)
        {
            $sid = p::strongId();
            $sid[0] = dechex(mt_rand(0, 15));
            self::$sslid = (isset($_SERVER['HTTPS']) ? '' : '-') . p::strongId();
            self::setSID($sid);

            self::$adapter = new self(self::$SID);

            self::$lastseen = self::$birthtime = $_SERVER['REQUEST_TIME'];
        }
        else self::$sslid = $sid = '';

        setcookie('SID',         $sid, 0, self::$cookiePath, self::$cookieDomain, false, true);
        setcookie('SSL', self::$sslid, 0, self::$cookiePath, self::$cookieDomain, true , true);

        // 304 Not Modified response code does not allow Set-Cookie headers,
        // so we remove any header that could trigger a 304
        unset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE']);
    }

    static function destroy()
    {
        self::regenerateId(true, false);
    }

    static function close()
    {
        self::$adapter = false;
    }


    /* Internal methods */

    static function __init()
    {
        self::$savePath     = $CONFIG['session.save_path'];
        self::$cookiePath   = $CONFIG['session.cookie_path'];
        self::$cookieDomain = $CONFIG['session.cookie_domain'];

        $CONFIG['session.auth_vars']  && self::$authVars  = array_merge(self::$authVars , $CONFIG['session.auth_vars']);
        $CONFIG['session.group_vars'] && self::$groupVars = array_merge(self::$groupVars, $CONFIG['session.group_vars']);

        self::$authVars  = array_flip(self::$authVars);
        self::$groupVars = array_flip(self::$groupVars);

        if (self::$maxIdleTime<1 && self::$maxLifeTime<1) user_error('At least one of the SESSION::$max*Time variables must be strictly positive.');

        if (mt_rand(1, self::$gcProbabilityDenominator) <= self::$gcProbabilityNumerator)
        {
            $adapter = new self('0lastGC');
            $i = $adapter->read();
            $j = max(self::$maxIdleTime, self::$maxLifeTime);

            if ($j && $_SERVER['REQUEST_TIME'] - $i > $j)
            {
                $adapter->write($_SERVER['REQUEST_TIME']);
                register_shutdown_function(array(__CLASS__, 'gc'), $j);
            }

            unset($adapter);
        }

        if (isset($_COOKIE['SID']))
        {
            self::setSID($_COOKIE['SID']);
            self::$adapter = new self(self::$SID);
            $i = self::$adapter->read();
        }
        else $i = false;

        if ($i)
        {
            $i = unserialize($i);
            self::$lastseen =  $i[0];
            self::$birthtime = $i[1];

            if (self::$maxIdleTime && $_SERVER['REQUEST_TIME'] - self::$lastseen > self::$maxIdleTime)
            {
                // Session has idled
                self::onIdle();
                self::$isIdled = true;
            }
            else if (self::$maxLifeTime && $_SERVER['REQUEST_TIME'] - self::$birthtime > self::$maxLifeTime)
            {
                // Session has expired
                self::onExpire();
            }
            else self::$DATA =& $i[2];

            if (isset($_SERVER['HTTPS']) && (!isset($_COOKIE['SSL']) || $i[3] != $_COOKIE['SSL']))
            {
                self::regenerateId(true);
            }
            else
            {
                self::$sslid = $i[3];

                if ('-' == self::$sslid[0] && isset($_SERVER['HTTPS']))
                {
                    self::$sslid = p::strongId();
                    setcookie('SSL', self::$sslid, 0, self::$cookiePath, self::$cookieDomain, true , true);
                    unset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE']);
                }
            }
        }
        else self::regenerateId(true);
    }

    protected static function setSID($SID)
    {
        if (self::$IPlevel)
        {
            // Session <-> IP binding (IPv4 only)

            $IPs = '127.0.0.1,' . $_SERVER['REMOTE_ADDR']
                . ',' . (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '')
                . ',' . (isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '');

            preg_match_all('/(?<![\.\d])\d+(?:\.\d+){' . (self::$IPlevel - 1) . '}/u', $IPs, $IPs);

            sort($IPs[0]) && $IPs[0] = array_unique($IPs[0]);

            $IPs[1] = explode('.', $_SERVER['REMOTE_ADDR'], self::$IPlevel+1);
            unset($IPs[1][self::$IPlevel]);

            $IPs = implode('.', $IPs[1]) . ',' . implode(',', $IPs[0]);
        }
        else $IPs = '';

        self::$SID = md5($SID .'-'. $IPs);
    }

    protected static function onIdle()
    {
        self::regenerateId(true);
    }

    protected static function onExpire()
    {
        self::onIdle();
    }


    /* Adapter */

    protected

    $handle,
    $path;


    protected function __construct($sid)
    {
        $this->path = self::$savePath . '/' . $sid[0];
        file_exists($this->path) || mkdir($this->path, 0700, true);
        $this->path .= '/' . substr($sid, 1) . '.session';
        $this->handle = fopen($this->path, 'a+b');
        flock($this->handle, LOCK_EX);
    }

    function __destruct()
    {
        if ($this->handle)
        {
            $this->write(serialize(array(
                self::$isIdled ? self::$lastseen : $_SERVER['REQUEST_TIME'],
                self::$birthtime,
                self::$DATA,
                self::$sslid
            )));

            flock($this->handle, LOCK_UN);
            fclose($this->handle);
        }
    }

    protected function read()
    {
        return stream_get_contents($this->handle);
    }

    protected function write($value)
    {
        ftruncate($this->handle, 0);
        fwrite($this->handle, $value);
    }

    protected function reset()
    {
        ftruncate($this->handle, 0);
        flock($this->handle, LOCK_UN);
        fclose($this->handle);
        $this->handle = false;

        unlink($this->path);
    }

    static function gc($lifetime)
    {
        for ($i = 0; $i < 16; ++$i)
        {
            $dir = self::$savePath . '/' . dechex($i) . '/';

            if (file_exists($dir))
            {
                $h = opendir($dir);
                while (false !== $file = readdir($h))
                    '.session' === substr($file, -8)
                        && $_SERVER['REQUEST_TIME'] - filemtime($dir . $file) > $lifetime
                        && unlink($dir . $file);
                closedir($h);
            }
        }
    }
}
