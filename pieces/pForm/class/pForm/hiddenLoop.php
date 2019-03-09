<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

class pForm_hiddenLoop extends loop
{
    protected $generator, $array, $count;

    function __construct(&$array)
    {
        $this->count = count($array);
        $this->generator = function () use ($array) {
            yield;

            foreach ($array as $v) {
                yield $v;
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

            return false;
        }

        $value = $this->array->current();
        $result = $value->loop();
        $value->loop();

        return $result;
    }
}
