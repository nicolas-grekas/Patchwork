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

use Patchwork           as p;
use Patchwork\Exception as e;
use SESSION             as s;

class Clientside extends p
{
    static function loadAgent($agent)
    {
        p::setMaxage(-1);
        p::setPrivate();
        p::setExpires('onmaxage');

        $a = p::agentArgs($agent);
        $a = implode(',', array_map('jsquote', $a));

        $agent = jsquote('agent_index' === $agent ? '' : p\Superloader::class2file(substr($agent, 6)));

        $lang = p::__LANG__();
        $appId = p::$appId;
        $base = p::__BASE__();

        if (PATCHWORK_I18N)
        {
            ob_start();
            self::writeAgent(new \loop_altLang);
            $b = substr(ob_get_clean(), 4, -1);
        }
        else $b = '0';

        $lang = $lang ? " lang=\"{$lang}\"" : '';
        $uri  = p::__URI__();
        $uri .= (false === strpos($uri, '?') ? '?' : '&') . 'p:=serverside';
        false !== strpos($uri, '<') && $uri = str_replace('<', '%3C', $uri);
        false !== strpos($uri, '>') && $uri = str_replace('>', '%3E', $uri);

        echo $a =<<<EOHTML
<!doctype html>
<!--[if lt IE 7]><html{$lang} class="lt-ie9 lt-ie8 lt-ie7 ie6"><![endif]-->
<!--[if IE 7]><html{$lang} class="lt-ie9 lt-ie8 ie7"><![endif]-->
<!--[if IE 8]><html{$lang} class="lt-ie9 ie8"><![endif]-->
<!--[if gt IE 8]><!--><html{$lang}><!--<![endif]-->
<meta charset="utf-8">
<noscript><meta http-equiv="refresh" content="0; url={$uri}"></noscript>
<script name="w$">a=[{$agent},[{$a}],{$appId},{$b}]</script>
<!--[if !IE]><!--><script name="w$" src="data:text/javascript,a[4]=1"></script><!--<![endif]-->
<script src="{$base}js/w?{$appId}"></script>
EOHTML;
    }

    static function render($agent, $liveAgent)
    {
        $config_maxage = $CONFIG['maxage'];

        // Get the calling URI
        if (isset($_COOKIE['R$']))
        {
            p::$uri = $_COOKIE['R$'];

            setcookie('R$', '', 1, '/');

            // Check the Referer header
            // T$ starts with 2 when the Referer's confidence is unknown
            //                1 when it is trusted
            if (isset($_SERVER['HTTP_REFERER']) && $_COOKIE['R$'] === $_SERVER['HTTP_REFERER'])
            {
                if (class_exists('SESSION', false))
                {
                    $_COOKIE['T$'] = '1';
                    s::regenerateId();
                }
                else
                {
                    self::$antiCsrfToken[0] = '1';
                    setcookie('T$', self::$antiCsrfToken, 0, $CONFIG['session.cookie_path'], $CONFIG['session.cookie_domain']);
                }
            }
        }
        else p::$uri = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : p::$base;

        if ($liveAgent)
        {
            // The output is both html and js, but iframe transport layer needs html
            p::$binaryMode = true;
            header('Content-Type: text/html');

            echo '/*<script>/**/q="';
        }
        else echo 'w(';

        p::openMeta($agent);

        try
        {
            if (isset($_GET['T$']) && !p::$antiCsrfMatch) throw new e\PrivateResource;

            $a = new $agent($_GET);

            $group = p::closeGroupStage();

            if ($is_cacheable = 'POST' !== $_SERVER['REQUEST_METHOD'] && !in_array('private', $group))
            {
                $cagent = p::agentCache($agent, $a->get, 'js.ser', $group);
                $dagent = p::getContextualCachePath('jsdata.' . $agent, 'js.ser', $cagent);

                if ($liveAgent)
                {
                    if (file_exists($dagent))
                    {
                        if (filemtime($dagent) > $_SERVER['REQUEST_TIME'])
                        {
                            $data = unserialize(file_get_contents($dagent));
                            p::setMaxage($data['maxage']);
                            p::setExpires($data['expires']);
                            p::writeWatchTable($data['watch']);
                            array_map('header', $data['headers']);
                            p::closeMeta();

                            echo str_replace(array('\\', '"', '</'), array('\\\\', '\\"', '<\\/'), $data['rawdata']),
                                '"//</script><script src="' . p::__BASE__() . 'js/QJsrsHandler"></script>';

                            return;
                        }
                        else @(unlink($cagent) + unlink($dagent));
                    }
                }
                else
                {
                    if (file_exists($cagent))
                    {
                        if (filemtime($cagent) > $_SERVER['REQUEST_TIME'])
                        {
                            $data = unserialize(file_get_contents($cagent));
                            p::setMaxage($data['maxage']);
                            p::setExpires($data['expires']);
                            p::writeWatchTable($data['watch']);
                            array_map('header', $data['headers']);
                            p::closeMeta();

                            echo $data['rawdata'];

                            return;
                        }
                        else @(unlink($cagent) + unlink($dagent));
                    }
                }
            }

            ob_start();
            ++p::$ob_level;

            try
            {
                $data = (object) $a->compose((object) array());

                if (!p::$is_enabled)
                {
                    p::closeMeta();
                    return;
                }

                $template = $a->getTemplate();

                echo '{';

                $comma = '';
                foreach ($data as $key => $value)
                {
                    $key = jsquote($key);
                    is_string($key) || $key = "'" . $key . "'";
                    echo $comma, $key, ':';
                    if ($value instanceof \loop) self::writeAgent($value);
                    else echo jsquote($value);
                    $comma = ',';
                }

                echo '}';
            }
            catch (e\PrivateResource $data)
            {
                ob_end_clean();
                --p::$ob_level;
                p::closeMeta();
                throw $data;
            }

            $data = ob_get_clean();
            --p::$ob_level;

            $a->metaCompose();
            list($maxage, $group, $expires, $watch, $headers) = p::closeMeta();
        }
        catch (e\PrivateResource $data)
        {
            if ($liveAgent)
            {
                echo 'false";(window.E||alert)("You must provide an auth token to get this liveAgent:\\n"+', jsquote($_SERVER['REQUEST_URI']), ')';
                echo '//</script><script src="' . p::__BASE__() . 'js/QJsrsHandler"></script>';
            }
            else if ($data->getMessage())
            {
                echo 'w.r(0,' . (int)!DEBUG . '));';
            }
            else
            {
                echo ');window.E&&E("You must provide an auth token to get this agent:\\n"+', jsquote($_SERVER['REQUEST_URI']), ')';
            }

            exit;
        }

        if ($liveAgent)
        {
            echo str_replace(array('\\', '"', '</'), array('\\\\', '\\"', '<\\/'), $data),
                '"//</script><script src="' . p::__BASE__() . 'js/QJsrsHandler"></script>';
        }
        else echo $data;

        if ('ontouch' === $expires && !($watch || $config_maxage == $maxage)) $expires = 'auto';
        $expires = 'auto' === $expires && ($watch || $config_maxage == $maxage) ? 'ontouch' : 'onmaxage';

        $is_cacheable = $is_cacheable && !in_array('private', $group) && ($maxage || 'ontouch' === $expires);

        if (!$liveAgent || $is_cacheable)
        {
            if ($is_cacheable) ob_start();

            if ($config_maxage == $maxage && Superloader::$turbo)
            {
                $ctemplate = p::getContextualCachePath("templates/$template", 'txt');

                $readHandle = true;

                if ($h = p::fopenX($ctemplate, $readHandle))
                {
                    p::openMeta('agent__template/' . $template, false);
                    $template = new \ptlCompiler_js($template);
                    echo $template = ',' . $template->compile() . ')';
                    fwrite($h, $template);
                    flock($h, LOCK_UN);
                    fclose($h);
                    list(,,, $template) = p::closeMeta();
                    p::writeWatchTable($template, $ctemplate);
                }
                else
                {
                    fpassthru($readHandle);
                    flock($readHandle, LOCK_UN);
                    fclose($readHandle);
                }

                $watch[] = 'public/templates/js';
            }
            else echo ',[1,', jsquote($template), ',0,0,0])';

            if ($is_cacheable)
            {
                $ob = true;

                $template = array(
                    'maxage' => $maxage,
                    'expires' => $expires,
                    'watch'   => $watch,
                    'headers' => $headers,
                    'rawdata' => $data,
                );

                $expires = 'ontouch' === $expires ? $config_maxage : $maxage;

                if ($h = p::fopenX($dagent))
                {
                    fwrite($h, serialize($template));
                    flock($h, LOCK_UN);
                    fclose($h);

                    touch($dagent, $_SERVER['REQUEST_TIME'] + $expires);

                    p::writeWatchTable($watch, $dagent);
                }

                if ($h = p::fopenX($cagent))
                {
                    $ob = false;
                    $template['rawdata'] .= $liveAgent ? ob_get_clean() : ob_get_flush();

                    fwrite($h, serialize($template));
                    flock($h, LOCK_UN);
                    fclose($h);

                    touch($cagent, $_SERVER['REQUEST_TIME'] + $expires);

                    p::writeWatchTable($watch, $cagent);
                }

                if ($ob) $liveAgent ? ob_end_clean() : ob_end_flush();
            }
        }
    }

    protected static function writeAgent($loop)
    {
        if ($prevKeys = $loop->__toString())
        {
            echo "w.x([", $prevKeys, ",[";

            $prevKeys = array();

            while ($data = $loop->loop())
            {
                $data = (array) $data;

                if ($prevKeys !== array_keys($data))
                {
                    $k = array_keys($data);

                    echo $prevKeys ? '],[' : '',
                        count($k), ',',
                        implode(',', array_map('jsquote', $k));

                    $prevKeys = $k;
                }

                foreach ($data as $value)
                {
                    echo ',';
                    if ($value instanceof \loop) self::writeAgent($value);
                    else echo jsquote($value);
                }
            }

            echo ']])';
        }
        else echo '0';
    }
}
