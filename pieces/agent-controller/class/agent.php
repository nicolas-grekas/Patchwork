<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/

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

    // By default, equals to contentType const if it's not empty
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

        $this->contentType = constant($class . '::contentType');

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
