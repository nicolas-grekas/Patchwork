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
