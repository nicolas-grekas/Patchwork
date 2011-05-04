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


class agent_css extends agent
{
    const contentType = 'text/css';

    public $get = '__0__';

    protected

    $maxage = -1,
    $watch = array('public/css'),
    $extension = '.css';


    function control()
    {
        $dir = substr(get_class($this), 6);
        $dir = patchwork_class2file($dir);

        $tpl = $this->get->__0__;

        if ($tpl !== '')
        {
            if ($this->extension !== substr($tpl, -3)) $tpl .= $this->extension;

            $tpl = str_replace('../', '/', $dir . '/' . strtr($tpl, '\\', '/'));
        }
        else $tpl = $dir . $this->extension;

        $this->template = $tpl;
    }
}
