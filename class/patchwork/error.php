<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
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

use patchwork as p;

class patchwork_error
{
    static function handle($code, $message, $file, $line)
    {
        if (class_exists('patchwork', false))
        {
            p::setMaxage(0);
            p::setExpires('onmaxage');
            $GLOBALS['patchwork_private'] = true;
        }

        $callee = '';
        $context = '';

        if (!ob::$in_handler)
        {
            $msg = debug_backtrace();

            $context = array();
            $i = 0;
            $length = count($msg);
            while ($i < $length)
            {
                $a = @array(
                    ' in   ' => "{$msg[$i]['file']} line {$msg[$i]['line']}",
                    ' call ' => (isset($msg[$i]['class']) ? $msg[$i]['class'].$msg[$i]['type'] : '') . $msg[$i]['function'] . '()'
                );

                switch ($a[' call '])
                {
                case 'patchwork_error_handler()':
                    $context = array();
                    unset($msg[$i]['args'][0], $msg[$i]['args'][2], $msg[$i]['args'][3], $msg[$i]['args'][4]);
                    break;

                case 'require()':
                case 'require_once()':
                case 'include()':
                case 'include_once()':
                    $a = array();
                    break;

                case 'trigger_error()':
                    $context = array();
                    $j = $i+1;

                    if (isset($msg[$j]['class']))
                    {
                        for (++$j; $j < $length; ++$j)
                        {
                            if (!isset($msg[$j]['class']) || $msg[$j]['class'] !== $msg[$i+1]['class'])
                            {
                                --$j;

                                $callee = $msg[$j]['class'];
                                $callee = substr($callee, 0, strpos($callee, '__'));
                                $callee .= $msg[$j]['type'] . $msg[$j]['function'] . '([…])';

                                $file = $msg[$j]['file'];
                                $line = $msg[$j]['line'];

                                break;
                            }
                        }
                    }

                    break;
                }

                if ($a)
                {
                    empty($msg[$i]['args']) || $a[' args '] = array_map(array(__CLASS__, 'filterArgs'), $msg[$i]['args']);
                    $context[] = $a;
                }

                ++$i;
            }

            $context[] = array('_SERVER' => &$_SERVER);

            $context = htmlspecialchars( print_r($context, true) );
        }

        switch ($code)
        {
        case E_ERROR:
        case E_USER_ERROR:        $msg = '<b>Fatal Error</b>';   break;
        case E_WARNING:
        case E_USER_WARNING:      $msg = '<b>Warning</b>';       break;
        case E_NOTICE:
        case E_USER_NOTICE:       $msg = '<b>Notice</b>';        break;
        case E_STRICT:            $msg = '<b>Strict Notice</b>'; break;
        case E_DEPRECATED:
        case E_USER_DEPRECATED:   $msg = '<b>Deprecated</b>';    break;
        case E_RECOVERABLE_ERROR: $msg = '<b>Fatal Recoverable Error</b>'; break;
        default:                  $msg = '<b>Unknown Error (#' . $code . ')</b>';
        }

        $date = date('d-M-Y H:i:s');
        $callee && $callee = " calling <b>{$callee}</b>";

        $cid = md5(uniqid(mt_rand()));
        $cid = <<<EOHTML
<script>
focus()
L=opener||parent;
L=L&&L.document.getElementById('debugLink')
L=L&&L.style
if(L)
{
L.backgroundColor='red'
L.fontSize='18px'
}
</script><a href="javascript:;" onclick="var a=document.getElementById('{$cid}');a.style.display=a.style.display?'':'none';" style="color:red;font-weight:bold" title="[{$date}]">{$msg}</a>
in <b>{$file}</b> line <b>{$line}</b>{$callee}:\n{$message}<blockquote id="{$cid}" style="display:none">Context: {$context}</blockquote><br><br>
EOHTML;

        $i = ini_get('error_log');
        $i = fopen($i ? $i : PATCHWORK_PROJECT_PATH . 'error.patchwork.log', 'ab');
        fwrite($i, $cid);
        fclose($i);

        switch ($code)
        {
        case E_ERROR:
        case E_USER_ERROR:
        case E_RECOVERABLE_ERROR:
            exit;
        }
    }

    protected static function filterArgs($a, $k = true)
    {
        switch (gettype($a))
        {
        case 'array':
            if ($k)
            {
                $b = array();

                foreach ($a as $k => &$v) $b[$k] = self::filterArgs($v, false);
            }
            else $b = 'array(...)';

            return $b;

        case 'object' : return '(object) ' . get_class($a);
        case 'string' : return '(string) ' . $a;
        case 'boolean': return $a ? 'true' : 'false';
        }

        return $a;
    }
}
