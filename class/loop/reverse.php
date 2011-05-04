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


class loop_reverse extends loop_array
{
    function __construct($loop)
    {
        $array = $this->getArray($loop, true);

        parent::__construct($array, 'filter_rawArray');
    }

    function getArray($loop, $unshift = false)
    {
        $array = array();

        while ($a = $loop->loop())
        {
            foreach ($a as &$v) if ($v instanceof loop) $v = new loop_array($this->getArray($v), 'filter_rawArray');

            $unshift ? array_unshift($array, $a) : ($array[] = $a);
        }

        return $array;
    }
}
