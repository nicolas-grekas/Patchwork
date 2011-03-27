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
            $src = patchwork_class2file(substr(get_class($this), 6));
            $src = Patchwork\Serverside::returnAgent($src, (array) $this->get);
            --self::$recursion;

            $parser = new jsqueez;

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
