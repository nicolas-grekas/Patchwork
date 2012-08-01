<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class agentHelper
{
    public $data;

    function __construct($data = false)
    {
        $this->data = $data ? $data : (object) array();
    }

    function setVar($varname, $value)
    {
        $this->data->$varname =& $value;
    }

    function setVars($vararray)
    {
        while (list($key) = each($vararray)) $this->data->$key =& $vararray[$key];
    }

    function setBlock($blockname, $vararray)
    {
        $blocks = explode('.', $blockname);
        $blockcount = count($blocks)-1;
        $loop =& $this->data;

        $i = -1;
        while (++$i < $blockcount)
        {
            $loop =& $loop->{$blocks[$i]}->array;
            $loop =& $loop[ count($loop)-1 ];
        }

        if (isset($loop->{$blocks[$i]})) $loop->{$blocks[$i]}->array[] = (object) $vararray;
        else $loop->{$blocks[$i]} = new agentHelper_blockContainer__((object) $vararray);
    }
}

class agentHelper_blockContainer__ extends loop
{
    public $array;

    function __construct($data) {$this->array = array($data);}
    protected function prepare() {return count($this->array);}
    protected function next()
    {
        return (list(, $value) = each($this->array)) ? $value : (reset($this->array) && false);
    }
}
