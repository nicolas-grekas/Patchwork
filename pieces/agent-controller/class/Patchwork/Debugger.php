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

        $handlers = array();

        for (;;)
        {
            foreach (scandir(PATCHWORK_ZCACHE) as $log)
                if ('.log' === substr($log = PATCHWORK_ZCACHE . $log, -4))
                    if (rename($log, $log .= '~'))
                        ($h = fopen($log, 'rb')) ? $handlers[$log] = new p\PHP\JsonDumpIterator($h) : unlink($log);

            $count = 0;

            foreach ($handlers as $log => $h)
            {
                try
                {
                    foreach ($h as $j)
                    {
                        echo '<script>patchworkConsole.log(',
                            $h->jsonStr($j['type']), ',',
                            $j['json'], ',',
                            $h->jsonStr(substr(md5($log), -10)),
                            ')</script>', "\n";
                        ob_flush();
                        flush();

                        if (connection_aborted()) break 3;
                    }

                    ++$count;
                }
                catch (p\PHP\JsonDumpIteratorException $h)
                {
                }
            }

            if ($count === count($handlers)) break;

            usleep(150000);
        }

        foreach ($handlers as $log => $h) fclose($h->getStream()) + unlink($log);

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
