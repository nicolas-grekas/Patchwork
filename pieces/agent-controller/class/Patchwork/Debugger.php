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
    <link type="text/css" rel="stylesheet" href="<?php echo p::__BASE__() . 'css/patchwork-console.css?' . $GLOBALS['patchwork_appId'];?>">
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
                        ($h = fopen($log, 'rb')) ? $handlers[$log] = $h : unlink($log);

            $count = 0;

            foreach ($handlers as $log => $h)
            {
                if (false === $next_line = fgets($h)) ++$count;
                else while (false !== $line = $next_line)
                {
                    $next_line = fgets($h);

                    self::parseLine($line, $next_line);

                    for (;;)
                    {
                        if (false !== $line = reset(self::$buffer))
                        {
                            echo implode('', $line);

                            if ($line && false === end($line))
                            {
                                unset(self::$buffer[key(self::$buffer)]);

                                ob_flush();
                                flush();

                                if (connection_aborted()) break 4;
                                else continue;
                            }
                            else
                            {
                                self::$buffer[key(self::$buffer)] = array();
                            }
                        }

                        break;
                    }
                }
            }

            if ($count === count($handlers)) break;

            usleep(150000);
        }

        foreach ($handlers as $log => $h) fclose($h) + unlink($log);

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

    protected static function parseLine($line, $next_line)
    {
        if ('' !== $line && '[' === $line[0] && (false !== $offset = strpos($line, ']')) && '] PHP ' === substr($line, $offset, 6))
        {
            static $raw_token; empty($raw_token) && $raw_token = substr(mt_rand(), -10);

            $offset += 6;

            if (preg_match("' on line \d+$'", $line))
            {
                $line = self::parseRawError($line, $offset);
                $line = array(
                    '*** php-error ***',
                    '{',
                    '  "time": ' . p\PHP\JsonDumper::get($line['date']) . ',',
                    '  "data": {',
                    '    "mesg": ' . p\PHP\JsonDumper::get($line['message']) . ',',
                    '    "code": ' . p\PHP\JsonDumper::get("{$line['type']} {$line['file']}:{$line['line']}") . ',',
                    '    "level": ' . p\PHP\JsonDumper::get(constant($line['type']) . '/-1'),
                );

                if ("Stack trace:" === substr(rtrim($next_line), $offset))
                {
                    $line[count($line) - 1] .= ',';
                }
            }
            else
            {
                // Xdebug inserted stack trace

                $line = substr(rtrim($line), $offset);

                if ("Stack trace:" === $line)
                {
                    $line = array('    "trace": {');
                }
                else
                {
                    // TODO: more extensive parsing of dumped arguments using token_get_all() / client-side parsing?

                    preg_match("' +(\d+)\. (.+?)\((.*)\) (.*)$'", $line, $line);

                    $line = array(
                        '      "' . $line[1] . '": {',
                        '        "call": ' . p\PHP\JsonDumper::get("{$line[2]}() {$line[4]}") . ('' !== $line[3] ? ',' : ''),
                        '' !== $line[3] ? '        "args": ' . p\PHP\JsonDumper::get($line[3]) : null,
                        '      }'
                    );

                    if ('[' !== $next_line[0] || (false === $offset = strpos($next_line, ']')) || '] PHP ' !== substr($next_line, $offset, 6) || preg_match("' on line \d+$'", $next_line))
                    {
                        $line[] = '    }';
                    }
                    else
                    {
                        $line[count($line) - 1] .= ',';
                    }
                }
            }

            if ('[' !== $next_line[0] || (false === $offset = strpos($next_line, ']')) || '] PHP ' !== substr($next_line, $offset, 6) || preg_match("' on line \d+$'", $next_line))
            {
                $line[] = '  }';
                $line[] = '}    ';
                $line[] = '***';
            }

            foreach ($line as $line) null !== $line && self::htmlDumpLine("{$raw_token}: {$line}\n");
        }
        else
        {
            self::htmlDumpLine($line);
        }
    }

    protected static function parseRawError($a, $offset)
    {
        $b = strpos($a, ':', $offset + 1);
        $b = array(
            'date' => substr($a, 1, $offset - 7),
            'type' => substr($a, $offset, $b - $offset),
            'message' => rtrim(substr($a, $b+3)),
            'file' => '',
            'line' => 0,
        );

        $b['date'] = date('c', strtotime($b['date']));

        static $msg_map = array(
            'Notice' => 'E_NOTICE',
            'Warning' => 'E_WARNING',
            'Deprecated' => 'E_DEPRECATED',
            'Fatal error' => 'E_ERROR',
            'Parse error' => 'E_PARSE',
            'Strict standards' => 'E_STRICT', // From Xdebug
            'Strict Standards' => 'E_STRICT',
            'Catchable fatal error' => 'E_RECOVERABLE_ERROR',
        );

        if (isset($msg_map[$b['type']]))
        {
            $b['type'] = $msg_map[$b['type']];
        }

        if (preg_match('/^(.*) in (.*) on line (\d+)$/s', $b['message'], $a))
        {
            $b['message'] = $a[1];
            $b['file'] = $a[2];
            $b['line'] = $a[3];
        }

        return $b;
    }

    protected static function htmlDumpLine($a)
    {
        list($token, $a) = explode(': ', rtrim($a) , 2);

        $t = $token . '-';

        if ('*** ' === substr($a, 0, 4))
        {
            $t .= empty(self::$bufferDepth[$token])
                ? self::$bufferDepth[$token] = 1
                : ++self::$bufferDepth[$token];

            self::$buffer[$t][] = '<script>patchworkConsole.log(' . p\PHP\JsonDumper::get(substr($a, 4, -4)) . ',';
        }
        else if (!empty(self::$bufferDepth[$token]))
        {
            $t .= self::$bufferDepth[$token];

            if ('***' === $a)
            {
                self::$buffer[$t][] = ',' . p\PHP\JsonDumper::get($token) . ')</script>';
                self::$buffer[$t][] = false;

                if (0 === --self::$bufferDepth[$token]) unset(self::$bufferDepth[$token]);
            }
            else
            {
                self::$buffer[$t][] = $a . "\n";
            }
        }
        else
        {
            user_error('Invalid debug stream: this point has not reason to be reached', E_USER_WARNING);
        }
    }
}
