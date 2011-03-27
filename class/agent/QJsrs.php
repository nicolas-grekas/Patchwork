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


class agent_QJsrs extends agent
{
    const contentType = '';

    protected $data = array();

    function compose($o)
    {
        $o->DATA = '/*<script>/**/q="'
            . str_replace(array('\\', '"'), array('\\\\', '\\"'), $this->getJs($this->data))
            . '"//</script>'
            . '<script src="' . Patchwork::__BASE__() . 'js/QJsrsHandler"></script>';

        return $o;
    }

    protected function getJs($data)
    {
        if (is_object($data) || is_array($data))
        {
            $a = '{';

            foreach ($data as $k => $v)
            {
                $k = jsquote($k);
                is_string($k) || $k = "'" . $k . "'";
                $a .= $k . ':' . $this->getJs($v) . ',';
            }

            $k = strlen($a);
            if ($k > 1) $a{strlen($a)-1} = '}';
            else $a = '{}';
        }
        else $a = jsquote($data);

        return $a;
    }
}
