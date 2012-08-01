<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class loop
{
    private

    $loopLength = false,
    $filter = array();


    function __construct($filter = '')
    {
        $filter && $this->addFilter($filter);
    }

    protected function prepare() {}
    protected function next() {}

    final public function &loop($escape = false)
    {
        $catchMeta = Patchwork::$catchMeta;
        Patchwork::$catchMeta = true;

        if ($this->loopLength === false) $this->loopLength = (int) $this->prepare();

        if (!$this->loopLength) $data = false;
        else
        {
            $data = $this->next();
            if ($data || is_array($data))
            {
                $data = (object) $data;
                $i = 0;
                $len = count($this->filter);
                while ($i < $len) $data = (object) call_user_func($this->filter[$i++], $data, $this);
            }
            else $this->loopLength = false;
        }

        Patchwork::$catchMeta = $catchMeta;

        return $data;
    }

    final public function addFilter($filter) {if ($filter) $this->filter[] = $filter;}

    final public function __toString()
    {
        $catchMeta = Patchwork::$catchMeta;
        Patchwork::$catchMeta = true;

        if ($this->loopLength === false) $this->loopLength = (int) $this->prepare();

        Patchwork::$catchMeta = $catchMeta;

        return (string) $this->loopLength;
    }

    final public function getLength()
    {
        return (int) $this->__toString();
    }
}
