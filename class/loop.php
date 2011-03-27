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
