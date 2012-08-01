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
use SESSION   as s;

class StaticResource extends p
{
    // Map from extensions to content-types
    // This list doesn't have to be exhaustive:
    // only types which can be handled by the browser
    // or one of its plugin need to be listed here.

    static $contentType = array(
        '.html' => 'text/html',
        '.htm'  => 'text/html',
        '.css'  => 'text/css',
        '.js'   => 'text/javascript',
        '.htc'  => 'text/x-component',
        '.xml'  => 'application/xml',
        '.swf'  => 'application/x-shockwave-flash',

        '.png'  => 'image/png',
        '.gif'  => 'image/gif',
        '.jpg'  => 'image/jpeg',
        '.jpeg' => 'image/jpeg',
        '.ico'  => 'image/x-icon',
        '.svg'  => 'image/svg+xml',

        '.doc'  => 'application/msword',
        '.pdf'  => 'application/pdf',
    );


    static function sendTemplate($template)
    {
        $template = str_replace('\\', '/', $template);
        $template = str_replace('../', '/', $template);

        echo 'w(0';

        $ctemplate = p::getContextualCachePath("templates/$template", 'txt');

        Superloader::$turbo || p::syncTemplate($template, $ctemplate);

        $readHandle = true;

        if ($h = p::fopenX($ctemplate, $readHandle))
        {
            p::openMeta('agent__template/' . $template, false);
            $template = new \ptlCompiler_js($template);
            echo $template = ',' . $template->compile() . ')';
            fwrite($h, $template);
            flock($h, LOCK_UN);
            fclose($h);
            list(,,, $watch) = p::closeMeta();
            p::writeWatchTable($watch, $ctemplate);
        }
        else
        {
            fpassthru($readHandle);
            flock($readHandle, LOCK_UN);
            fclose($readHandle);
        }

        p::setMaxage(-1);
    }

    static function sendPipe($pipe)
    {
        preg_match_all('/[a-zA-Z_0-9\x80-\xFF]+/', $pipe, $pipe);
        p::$agentClass = 'agent__pipe/' . implode('_', $pipe[0]);

        echo '(function(w){';

        foreach ($pipe[0] as $pipe)
        {
            echo 'w.P$', $pipe, '=';

/**/        if (DEBUG)
/**/        {
                ob_start();
                call_user_func(array('pipe_' . $pipe, 'js'));
                echo trim(ob_get_clean(), ';');
/**/        }
/**/        else
/**/        {
                $cpipe = p::getContextualCachePath('pipe/' . $pipe, 'js');
                $readHandle = true;
                if ($h = p::fopenX($cpipe, $readHandle))
                {
                    ob_start();
                    call_user_func(array('pipe_' . $pipe, 'js'));

                    $pipe = new \JSqueeze;
                    $pipe = $pipe->squeeze(ob_get_clean());
                    echo $pipe = trim($pipe, ';');

                    fwrite($h, $pipe);
                    flock($h, LOCK_UN);
                    fclose($h);
                    p::writeWatchTable('pipe', $cpipe);
                }
                else
                {
                    fpassthru($readHandle);
                    flock($readHandle, LOCK_UN);
                    fclose($readHandle);
                }
/**/        }

            echo ';';
        }

        echo '})(window);w()';

        p::setMaxage(-1);
    }


    protected static $filterRx;

    static function readfile($file, $mime = true, $filename = true)
    {
        $h = patchworkPath($file);

        if (!$h || !file_exists($h) || is_dir($h))
        {
            user_error(__METHOD__ . "(..): invalid file ({$file})");
            return;
        }

        $file = $h;

        if (true === $mime)
        {
            $mime = strtolower(strrchr($file, '.'));
            $mime = isset(self::$contentType[$mime]) ? self::$contentType[$mime] : false;
        }

        $mime || $mime = isset(p::$headers['content-type']) ? substr(p::$headers['content-type'], 14) : 'application/octet-stream';
        $mime = strtolower($mime);

        $head = 'HEAD' == $_SERVER['REQUEST_METHOD'];
        $gzip = p::gzipAllowed($mime);
        $filter = $gzip || $head || !$CONFIG['xsendfile'] || in_array($mime, self::$ieSniffedTypes_edit) || in_array($mime, p::$ieSniffedTypes_download);

        header('Content-Type: ' . $mime);

        if ($filename)
        {
            $filename = basename(true === $filename ? $_SERVER['PATCHWORK_REQUEST'] : $filename);
            $size = false;

            if (!$filter)
            {
                // Force IE>=8 to respect attachment content disposition
                header('X-Download-Options: noopen');
            }

            // It seems that IE assumes that filename is represented in its local system charset...
            // But we don't want to introduce "Vary: User-Agent" just because of this.

            if (('POST' === $_SERVER['REQUEST_METHOD'] || p::$private)
                &&   isset($_SERVER['HTTP_USER_AGENT'])
                &&  strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')
                && !strpos($_SERVER['HTTP_USER_AGENT'], 'Opera')
                && preg_match('/[\x80-\xFF]/', $filename))
            {
                if (stripos(p::$headers['content-type'], 'octet-stream') && preg_match('#(.*)(\.[- -,/-~]+)$#D', $filename, $size))
                {
                    // Don't search any rational here, it's IE...
                    header(
                        'Content-Disposition: attachment; filename='
                        . rawurlencode($size[1])
                        . str_replace('"', "''", $size[2])
                    );
                }
                else $filename = Patchwork\Utf8::toAscii($filename);
            }

            $size || header('Content-Disposition: attachment; filename="' . str_replace('"', "''", $filename) . '"');

            // If only RFC 2231 were in use... See http://greenbytes.de/tech/tc2231/
            //header('Content-Disposition: attachment; filename*=utf-8''" . rawurlencode($filename));
        }
        else if (false !== strpos($mime, 'html'))
        {
            header('P3P: CP="' . $CONFIG['P3P'] . '"');
            header('X-XSS-Protection: 1; mode=block');
        }

        $size = filesize($file);
        p::$ETag = $size .'-'. p::$LastModified .'-'. fileinode($file);
        p::$LastModified = filemtime($file);
        p::$binaryMode = true;
        p::disable();

        class_exists('SESSION'   , false) && s::close();
        class_exists('adapter_DB', false) && \adapter_DB::__free();


        $gzip || ob_start();
        $filter && ob_start(array(__CLASS__, 'ob_filterOutput'), 32768);


        // Transform relative URLs to absolute ones
        if ($gzip)
        {
            if (0 === strncasecmp($mime, 'text/css', 8))
            {
                self::$filterRx = "@([\s:]url\(\s*[\"']?)(?![/\\\\#\"']|[^\)\n\r:/\"']+?:)@i";
                ob_start(array(__CLASS__, 'filter'), 32768);
            }
            else if (0 === strncasecmp($mime, 'text/html', 9) || 0 === strncasecmp($mime, 'text/x-component', 16))
            {
                self::$filterRx = "@(<[^<>]+?\s(?:href|src)\s*=\s*[\"']?)(?![/\\\\#\"']|[^\n\r:/\"']+?:)@i";
                ob_start(array(__CLASS__, 'filter'), 32768);
            }
        }


        if ($filter)
        {
            $h = fopen($file, 'rb');
            echo $starting_data = fread($h, 256); // For p::ob_filterOutput to fix IE

            if ($gzip)
            {
                if ($head) ob_end_clean();
                $data = '';
                $starting_data = false;
            }
            else
            {
                ob_end_flush();
                $data = ob_get_clean();
                $size += strlen($data) - strlen($starting_data);
                $starting_data = $data == $starting_data;
            }
        }
        else $starting_data = true;


        if (!$head)
        {
            if ($starting_data && $CONFIG['xsendfile']) header(sprintf($CONFIG['xsendfile'], $file));
            else
            {
                if ($range = $starting_data && !$gzip)
                {
                    header('Accept-Ranges: bytes');

                    $range = isset($_SERVER['HTTP_RANGE']) ? p\HttpRange::negociate($size, p::$ETag, p::$LastModified) : false;
                }
                else header('Accept-Ranges: none');

                set_time_limit(0);
                ignore_user_abort(false);

                if ($range)
                {
                    unset(p::$headers['content-type']);
                    p\HttpRange::sendChunks($range, $h, $mime, $size);
                }
                else
                {
                    $gzip || header('Content-Length: ' . $size);
                    echo $data;
                    feof($h) || fpassthru($h);
                }
            }
        }


        $filter && fclose($h);
    }

    static function filter($buffer, $mode)
    {
        static $rest = '', $base;

        if (!isset($base))
        {
            $base = dirname($_SERVER['PATCHWORK_REQUEST'] . ' ');
            if (1 === strlen($base) && strspn($base, '/\\.')) $base = '';
            $base = p::__BASE__() . $base . '/';
        }

        $buffer = preg_split(self::$filterRx, $rest . $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);

        $len = count($buffer);
        for ($i = 1; $i < $len; $i += 2) $buffer[$i] .= $base;

        if (PHP_OUTPUT_HANDLER_END & $mode) $rest = '';
        else
        {
            --$len;
            $rest = substr($buffer[$len], 4096);
            $buffer[$len] = substr($buffer[$len], 0, 4096);
        }

        $buffer = implode('', $buffer);

        return $buffer;
    }
}
