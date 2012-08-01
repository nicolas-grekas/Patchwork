<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork           as p;
use Patchwork\Exception as e;

class agent
{
    const contentType = 'text/html';

    public $get = array();

    protected

    $template = '',
    $maxage  = 0,
    $expires = 'auto',
    $canPost = false,
    $watch = array(),

    // Defaults to static::contentType
    $contentType;


    function control() {}
    function compose($o) {return $o;}
    function getTemplate()
    {
        if ($this->template) return $this->template;

        $class = get_class($this);

        do
        {
            if ((false === $tail = strrpos($class, '__'))
                || ((false !== $tail = substr($class, $tail+2)) && '' !== trim($tail, '0123456789')))
            {
                $template = p\Superloader::class2file(substr($class, 6));
                if (p::resolvePublicPath($template . '.ptl')) return $template;
            }
        }
        while (__CLASS__ !== $class = get_parent_class($class));

        return 'bin';
    }

    final public function __construct($args = array())
    {
        $class = get_class($this);

        isset($this->contentType) or $this->contentType = constant($class . '::contentType');

        $a = (array) $this->get;

        $this->get = (object) array();
        $_GET = array();

        foreach ($a as $key => &$a)
        {
            if (is_string($key))
            {
                $default = $a;
                $a = $key;
            }
            else $default = '';

            false !== strpos($a, "\000") && $a = str_replace("\000", '', $a);

            if (false !== strpos($a, '\\'))
            {
                $a = strtr($a, array('\\\\' => '\\', '\\:' => "\000"));
                $a = explode(':', $a);
                $b = count($a);
                do false !== strpos($a[--$b], "\000") && $a[$b] = strtr($a[$b], "\000", ':');
                while ($b);
            }
            else $a = explode(':', $a);

            $key = array_shift($a);

            $b = isset($args[$key]) ? (string) $args[$key] : $default;
            false !== strpos($b, "\000") && $b = str_replace("\000", '', $b);

            if ($a)
            {
                $b = FILTER::get($b, array_shift($a), $a);
                if (false === $b) $b = $default;
            }

            $_GET[$key] = $this->get->$key = $b;
        }

        $this->control();

        if (!$this->contentType
            && '' !== $a = strtolower(pathinfo(p\Superloader::class2file($class), PATHINFO_EXTENSION)))
        {
            $this->contentType = isset(p\StaticResource::$contentType['.' . $a])
                ? p\StaticResource::$contentType['.' . $a]
                : 'application/octet-stream';
        }

        $this->contentType && header('Content-Type: ' . $this->contentType);
    }

    function metaCompose()
    {
        p::setMaxage($this->maxage);
        p::setExpires($this->expires);
        p::watch($this->watch);
        if ($this->canPost) p::canPost();
    }


    static function get($agent, $args = array())
    {
        $o = (object) array();

        try
        {
            $agent = p::resolveAgentClass($agent, $args);
            $agent = new $agent($args);
            $o = $agent->compose($o);
            $agent->metaCompose();
        }
        catch (e\Forbidden      $agent) {user_error("Forbidden acces detected" );}
        catch (e\Redirection    $agent) {user_error("HTTP redirection detected");}
        catch (e\StaticResource $agent) {}

        return $o;
    }
}
