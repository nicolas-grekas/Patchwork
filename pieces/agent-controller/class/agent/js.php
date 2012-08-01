<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class agent_js extends agent_css
{
    const contentType = 'text/javascript';

    public $get = array('__0__', 'src:b');

    protected

    $debug = DEBUG,
    $maxage = -1,
    $watch = array('public/js'),
    $extension = '.js';


    protected static $recursion = 0;


    function control()
    {
        $this->get->src && self::$recursion = 1;
        self::$recursion && $this->get->src = 1;

        if ($this->debug || $this->get->src) parent::control();
        else $this->template = 'bin';
    }

    function compose($o)
    {
        if ($this->debug || $this->get->src)
        {
            $o = parent::compose($o);

            $o->cookie_path     = $CONFIG['session.cookie_path'];
            $o->cookie_domain   = $CONFIG['session.cookie_domain'];
            $o->document_domain = $CONFIG['document.domain'];
            $o->maxage = $CONFIG['maxage'];
        }
        else
        {
            ++self::$recursion;
            $src = Patchwork\Superloader::class2file(substr(get_class($this), 6));
            $src = Patchwork\Serverside::returnAgent($src, (array) $this->get);
            --self::$recursion;

            $parser = new JSqueeze;

            if ('/*!' != substr(ltrim(substr($src, 0, 512)), 0, 3))
            {
                $o->DATA = Patchwork::__URI__();
                $o->DATA .= (false === strpos($o->DATA, '?') ? '?' : '&') . 'src=1';
                $o->DATA = "// Copyright & source: {$o->DATA}\n";

                foreach (count_chars($o->DATA, 1) as $k => $w) $parser->charFreq[$k] += $w;

                $o->DATA .= $parser->squeeze($src);
            }
            else $o->DATA = $parser->squeeze($src);
        }

        return $o;
    }
}
