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

namespace Patchwork;

use Patchwork as p;

class Debugger extends p
{
    static $syncCache = false;

    protected static $buffer = array();


    static function __constructStatic()
    {
        // Major browsers send a "Cache-Control: no-cache" only and only if a page is reloaded with
        // CTRL+F5, CTRL+SHIFT+R or location.reload(true). Usefull to trigger synchronization events.

        self::$syncCache = file_exists(PATCHWORK_PROJECT_PATH . '.patchwork.php')
            && filemtime(PATCHWORK_PROJECT_PATH . 'config.patchwork.php') > filemtime(PATCHWORK_PROJECT_PATH . '.patchwork.php')
                || (isset($_SERVER['HTTP_CACHE_CONTROL']) && 'no-cache' == $_SERVER['HTTP_CACHE_CONTROL']);
    }

    static function execute()
    {
        $GLOBALS['patchwork_appId'] = -$GLOBALS['patchwork_appId'];

        if ('debug' === p::$requestMode) self::sendDebugInfo();
        else if (self::$syncCache)
        {
            if ($h = @fopen(PATCHWORK_PROJECT_PATH . '.debugLock', 'xb'))
            {
                flock($h, LOCK_EX);

                @unlink(PATCHWORK_PROJECT_PATH . '.patchwork.php');

                global $patchwork_path;

                $dir = opendir(PATCHWORK_PROJECT_PATH);
                while (false !== $cache = readdir($dir)) if (preg_match('/^\.(.+)\.[^0]([^\.]+)\.zcache\.php$/D', $cache, $level))
                {
                    $file = patchwork_class2file($level[1]);
                    $level = $level[2];

                    if ('-' == substr($level, -1))
                    {
                        $level = -$level;
                        $file = substr($file, 6);
                    }

                    $file = $patchwork_path[PATCHWORK_PATH_LEVEL - $level] . $file;

                    if (!file_exists($file) || filemtime($file) >= filemtime(PATCHWORK_PROJECT_PATH . $cache)) @unlink(PATCHWORK_PROJECT_PATH . $cache);
                }
                closedir($dir);

                fclose($h);
            }
            else
            {
                $h = fopen(PATCHWORK_PROJECT_PATH . '.debugLock', 'rb');
                flock($h, LOCK_SH);
                fclose($h);
            }

            @unlink(PATCHWORK_PROJECT_PATH . '.debugLock');
        }
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
        $QDebug   = p::__BASE__() . 'js/QDebug.js?' . $GLOBALS['patchwork_appId'];

        return <<<EOHTML
<script src="{$QDebug}"></script>
EOHTML;
    }

    static function getConclusion()
    {
        $debugWin = p::__BASE__() . '?p:=debug:stop';

        return <<<EOHTML
<style>@media print { #debugWin {display:none;} }</style>
<script>E('Rendering time: ' + (+new Date - E.startTime) + ' ms');</script>
<div id="debugWin">
<input type="hidden" name="debugStore" id="debugStore" value="">
<div style="position:fixed;_position:absolute;top:0;right:0;z-index:254;background-color:white;visibility:hidden;width:100%; height: 50%" id="debugFrame"><iframe src="{$debugWin}" style="width:100%;height:100%" name="debugFrame"></iframe></div>
<div style="position:fixed;_position:absolute;top:0;right:0;z-index:255;font-family:arial;font-size:9px"><a href="javascript:;" onclick="var f=document.getElementById('debugFrame');if (f) f.style.visibility='hidden'==f.style.visibility?'visible':'hidden',document.getElementById('debugStore').value=f.style.visibility" style="background-color:blue;color:white;text-decoration:none;border:0;" id="debugLink">Debug</a></div>
<script>setTimeout(function(){var f=document.getElementById('debugFrame'),s=document.getElementById('debugStore');if (f&&s&&s.value)f.style.visibility=s.value},0)</script>
EOHTML;
    }

    static function sendDebugInfo()
    {
        ob_start(function_exists('ob_gzhandler') ? 'ob_gzhandler' : null, 1<<14);

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: max-age=0,private,must-revalidate');

        set_time_limit(0);
        ignore_user_abort(false);
        ini_set('error_log', PATCHWORK_PROJECT_PATH . 'error.patchwork.log');
        $error_log = ini_get('error_log');
        $error_log || $error_log = PATCHWORK_PROJECT_PATH . 'error.patchwork.log';

        ?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Window</title>
    <link type="text/css" rel="stylesheet" href="<?php echo p::__BASE__() . 'css/debug-console.css?' . $GLOBALS['patchwork_appId'];?>">
    <script src="<?php echo p::__BASE__() . 'js/debug-console.js?' . $GLOBALS['patchwork_appId'];?>"></script>
</head>
<body>
<div id="console">
<div id="php-errors"><h3>PHP Errors</h3></div>
<div id="E"><h3>E()</h3></div>
<div id="requests"><h3>Requests</h3></div>
</div>
<div id="events">
<?php

        if (is_file($error_log))
        {
            if ($h = @fopen($error_log, 'r'))
            {
                while (false !== $next_line = fgets($h))
                {
                    while (false !== $line = $next_line)
                    {
                        $next_line = fgets($h);

                        self::parseLine($line, $next_line);

                        for (;;)
                        {
                            if (false !== $line = reset(self::$buffer))
                            {
                                echo self::parseZcachefile(implode('', $line));

                                if ($line && false === end($line))
                                {
                                    unset(self::$buffer[key(self::$buffer)]);
                                    continue;
                                }
                                else
                                {
                                    self::$buffer[key(self::$buffer)] = array();
                                }
                            }

                            break;
                        }

                        if (connection_aborted()) break;
                    }

                    usleep(100000); // Wait 100ms
                }

                fclose($h);
            }

            @unlink($error_log);
        }

        ?>
<script>
scrollTo(0,0);
var i, b = window.parent && parent.E && parent.E.buffer;
for (i in b) classifyEvent("0000000000", "client-dump", b[i]);
parent.E.buffer = [];
</script></div></body></html>
<?php

        exit;
    }

    static function parseLine($line, $next_line)
    {
        if ('' !== $line && '[' === $line[0] && '] PHP ' === substr($line, 21, 6))
        {
            static $raw_token; empty($raw_token) && $raw_token = substr(mt_rand(), -10);

            if (preg_match("' on line \d+$'", $line))
            {
                $line = self::parseRawError($line);
                $line = array(
                    '*** php-error ***',
                    '{',
                    '  "time": ' . p\PHP\JsonDumper::get($line['date']) . ',',
                    '  "data": {',
                    '    "mesg": ' . p\PHP\JsonDumper::get($line['message']) . ',',
                    '    "code": ' . p\PHP\JsonDumper::get("{$line['type']} {$line['file']}:{$line['line']}") . ',',
                    '    "level": ' . p\PHP\JsonDumper::get(constant($line['type']) . '/-1'),
                );

                if ("Stack trace:" === substr(rtrim($next_line), 27))
                {
                    $line[count($line) - 1] .= ',';
                }
            }
            else
            {
                // Xdebug inserted stack trace

                $line = substr(rtrim($line), 27);

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

                    if ('[' !== $next_line[0] || '] PHP ' !== substr($next_line, 21, 6) || preg_match("' on line \d+$'", $next_line))
                    {
                        $line[] = '    }';
                    }
                    else
                    {
                        $line[count($line) - 1] .= ',';
                    }
                }
            }

            if ('[' !== $next_line[0] || '] PHP ' !== substr($next_line, 21, 6) || preg_match("' on line \d+$'", $next_line))
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

    static function parseRawError($a)
    {
        $b = strpos($a, ':', 28);
        $b = array(
            'date' => substr($a, 1, 20),
            'type' => substr($a, 27, $b-27),
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

        $a = explode(' on line ', $b['message']);
        $b['line'] = array_pop($a);

        $a = explode(' in ', implode(' on line ', $a), 2);
        $b['message'] = $a[0];
        $b['file'] = $a[1];

        return $b;
    }

    static function parseZcacheFile($a)
    {
        if (false !== strpos($a, '.zcache.php'))
        {
            // TODO: be more robust here: input and output are json-encoded, not plain text
            $a = preg_replace_callback(
                "'" . preg_quote(PATCHWORK_PROJECT_PATH . '.')
                    . "([^\\\\/]+)\.[01]([0-9]+)(-?)\.zcache\.php'",
                array(__CLASS__, 'filename'),
                $a
            );
        }

        return $a;
    }

    static function filename($m)
    {
        return $GLOBALS['patchwork_path'][PATCHWORK_PATH_LEVEL - ((int)($m[3].$m[2]))] . '/' . patchwork_class2file($m[1]);
    }

    static function htmlDumpLine($a)
    {
        list($token, $a) = explode(': ', substr($a, 0, -1) , 2);
        $b =& self::$buffer[$token];

        if ('*** ' === substr($a, 0, 4))
        {
            $b[] = '<script>classifyEvent('
                . p\PHP\JsonDumper::get($token) . ','
                . p\PHP\JsonDumper::get(substr($a, 4, -4)) . ',';
        }
        else if ('***' === $a)
        {
            $b[] = ")</script>";
            $b[] = false;
        }
        else
        {
            $b[] = $a . "\n";
        }
    }
}
