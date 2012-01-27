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
