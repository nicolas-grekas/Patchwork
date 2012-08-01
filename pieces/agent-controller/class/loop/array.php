<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class loop_array extends loop
{
    protected

    $array,
    $isAssociative = true;


    function __construct($array, $filter = '', $isAssociative = null)
    {
        reset($array);
        $this->array = $array;
        if ($filter) $this->addFilter($filter);
        $this->isAssociative = $isAssociative!==null ? $isAssociative : $filter!==false;
    }

    protected function prepare() {return count($this->array);}

    protected function next()
    {
        if (list($key, $value) = each($this->array))
        {
            $data = array('VALUE' => &$value);
            if ($this->isAssociative) $data['KEY'] =& $key;

            return (object) $data;
        }
        else reset($this->array);
    }
}

function filter_rawArray($data)
{
    return $data->VALUE;
}
