<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

abstract class Patchwork_AbstractStreamProcessor extends php_user_filter
{
    abstract function process($data);


    protected $uri;
    private $data = '', $bucket;
    private static $registry = array();

    static function register($filter = null, $class = null)
    {
        if (empty($filter)) $filter = new self;
        if (empty($class)) $class = get_class($filter);
        self::$registry[$class] = $filter;
        stream_filter_register($class, $class);
        return 'php://filter/read=' . $class . '/resource=';
    }

    function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in))
        {
            $this->data .= $bucket->data;
            $this->bucket = $bucket;
            $consumed = 0;
        }

        if ($closing)
        {
            $consumed += strlen($this->data);

            if (isset($this->bucket)) $bucket = $this->bucket;
            else $bucket = stream_bucket_new($this->stream, '');

            $f = self::$registry[get_class($this)];

            Patchwork_StreamFilter__lazyUriResolver::bind($this->stream, $f->uri);

            $bucket->data = $f->process($this->data);
            $bucket->datalen = strlen($bucket->data);
            stream_bucket_append($out, $bucket);

            $this->bucket = null;
            $this->data = '';

            return PSFS_PASS_ON;
        }

        return PSFS_FEED_ME;
    }
}

class Patchwork_StreamFilter__lazyUriResolver
{
    protected $uri, $stream;

    static function bind($stream, &$uri)
    {
        $uri = new self;
        $uri->stream = $stream;
        $uri->uri =& $uri;
    }

    function __toString()
    {
        if (isset($this->stream))
        {
            $u = stream_get_meta_data($this->stream);
            $u = $u['uri'];
            $this->stream = null;
            do $u = substr($u, 10 + stripos($u, '/resource=', 12));
            while (0 === strncasecmp($u, 'php://filter/', 13));
            if (false === $p = stream_resolve_include_path($u)) $this->uri = $u;
            else $this->uri = realpath($p);
        }

        return $this->uri;
    }
}
