<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


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
