<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork;

use Patchwork as p;

class Debugger extends p
{
    protected static

    $buffer = array(),
    $bufferDepth = array();


    static function execute()
    {
        $GLOBALS['patchwork_appId'] = -$GLOBALS['patchwork_appId'];

        if ('debug' !== p::$requestMode)
        {
            isset($_COOKIE['JS']) || self::quickReset();
            return;
        }

        switch (p::$requestArg)
        {
        case 'quickReset': self::quickReset(); break;
        case 'deepReset': self::deepReset(); break;
        default: self::sendDebugInfo(); break;
        }

        exit;
    }

    static function purgeZcache()
    {
        p::updateAppId();

        $a = $CONFIG['i18n.lang_list'][$_SERVER['PATCHWORK_LANG']];
        $a = implode($a, explode('__', $_SERVER['PATCHWORK_BASE'], 2));
        $a = preg_replace("'\?.*$'", '', $a);
        $a = preg_replace("'^https?://[^/]*'i", '', $a);
        $a = dirname($a . ' ');
        if (1 === strlen($a)) $a = '';

        setcookie('v$', p::$appId, $_SERVER['REQUEST_TIME'] + $CONFIG['maxage'], $a .'/');

        p::touch('');

        for ($i = 0; $i < 16; ++$i) for ($j = 0; $j < 16; ++$j)
        {
            $dir = PATCHWORK_ZCACHE . dechex($i) . '/' . dechex($j) . '/';

            if (file_exists($dir))
            {
                $h = opendir($dir);
                while (false !== $file = readdir($h)) '.' !== $file && '..' !== $file && unlink($dir . $file);
                closedir($h);
            }
        }
    }

    static function getProlog()
    {
        return '<script src="'
            . p::__BASE__() . 'js/patchwork-debugger.js?' . $GLOBALS['patchwork_appId']
            . '"></script><script>patchworkDebugger.start("'
            . p::__BASE__() . '")</script>';
    }

    static function getConclusion()
    {
        return '<input type="hidden" name="debugStore" id="debugStore" value=""><script>patchworkDebugger.stop()</script>';
    }

    protected static function quickReset()
    {
        p::touch('debugSync');
        file_exists($f = PATCHWORK_PROJECT_PATH . '.patchwork.paths.db') && unlink($f);
    }

    protected static function deepReset()
    {
        unlink(PATCHWORK_PROJECT_PATH . '.patchwork.php');

        self::purgeZcache();
        self::quickReset();

        $h = opendir(PATCHWORK_PROJECT_PATH);
        while (false !== $f = readdir($h))
        if ('.' === $f[0] && '.zcache.php' === substr($f, -11))
            @unlink(PATCHWORK_PROJECT_PATH . $f);
        closedir($h);
    }

    protected static function sendDebugInfo()
    {
        ob_start(function_exists('ob_gzhandler') ? 'ob_gzhandler' : null, 1<<14);

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: max-age=0,private,must-revalidate');

        set_time_limit(0);
        ignore_user_abort(true);

        ?>
<!doctype html>
<html>
<head>
    <title>Debug Window</title>
    <link rel="stylesheet" href="<?php echo p::__BASE__() . 'css/patchwork-console.css?' . $GLOBALS['patchwork_appId'];?>">
</head>
<body>
<script src="<?php echo p::__BASE__() . 'js/patchwork-console.js?' . $GLOBALS['patchwork_appId'];?>"></script>
<div id="events" style="display:none">
<?php

        for (;;)
        {
            $continue = false;

            foreach (scandir(PATCHWORK_ZCACHE) as $log)
            {
                if ('.log' !== substr($log = PATCHWORK_ZCACHE . $log, -4)) continue;

/**/            // On Windows only, rename() fails if the file is opened in an other process.
/**/            // We use this behavior to detect this and cancel sending the file.
/**/            if ('\\' === DIRECTORY_SEPARATOR)
/**/            {
                    if (!@rename($log, $log .= '~'))
                    {
                        $continue = true;
                        continue;
                    }
/**/            }

                if (!$h = @fopen($log, 'rb'))
                {
/**/                if ('\\' === DIRECTORY_SEPARATOR)
                        rename($log, substr($log, 0, -1));
                    continue;
                }

/**/            if ('\\' !== DIRECTORY_SEPARATOR)
/**/            {
                    usleep(1); // Give priority for locking to the error handler process

                    if (@flock($h, LOCK_EX+LOCK_NB, $j) && !$j) unlink($log);
                    else
                    {
                        $continue = true;
                        continue;
                    }
/**/            }

                $it = new p\PHP\JsonDumpIterator($h);

                try
                {
                    unset($j);
                    foreach ($it as $j)
                    {
                        echo '<script>patchworkConsole.log(',
                            $it->jsonStr($j['type']), ',',
                            $j['json'], ',',
                            $it->jsonStr(substr(md5($log), -10)),
                            ')</script>', "\n";
                        ob_flush();
                        flush();

                        if (connection_aborted())
                        {
                            $continue = false;
                            break;
                        }
                    }
                }
                catch (p\PHP\JsonDumpIteratorException $it)
                {
                }

                flock($h, LOCK_UN);
                fclose($h);

/**/            if ('\\' === DIRECTORY_SEPARATOR)
                    unlink($log);
            }

            if ($continue && isset($j)) usleep(150000);
            else break;
        }

        ?>
</div>
<script>
scrollTo(0,0);
var i, b = window.parent && parent.E && parent.E.buffer;
for (i in b) patchworkConsole.log("client-dump", b[i]);
parent.E.buffer = [];
</script>
<?php
    }
}
