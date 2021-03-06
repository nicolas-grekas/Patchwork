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

    $generator,
    $array,
    $count,
    $isAssociative = true;


    function __construct($array, $filter = '', $isAssociative = null)
    {
        $this->count = count($array);
        reset($array);
        if ($filter) $this->addFilter($filter);
        $this->isAssociative = $isAssociative!==null ? $isAssociative : $filter!==false;

        $this->generator = function () use ($array) {
            yield;

            foreach ($array as $k => $v) {
                yield $k => $v;
            }
        };
        $this->array = ($this->generator)();
    }

    protected function prepare() {return $this->count;}

    protected function next()
    {
        $this->array->next();

        if (!$this->array->valid()) {
            $this->array = ($this->generator)();

            return;
        }

        $key = $this->array->key();
        $value = $this->array->current();

        $data = array('VALUE' => &$value);
        if ($this->isAssociative) $data['KEY'] =& $key;

        return (object) $data;
    }
}

function filter_rawArray($data)
{
    return $data->VALUE;
}
