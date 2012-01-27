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


class pForm_jsSelect extends pForm_select
{
    protected $src;

    protected function init(&$param)
    {
        unset($param['item']);
        unset($param['sql']);
        isset($param['valid']) || $param['valid'] = 'char';

        parent::init($param);

        isset($param['src']) && $this->src = $param['src'];
    }

    protected function get()
    {
        $a = parent::get();

        $this->agent = 'form/jsSelect';

        if (isset($this->src)) $a->_src_ = $this->src;

        if ($this->status) $a->_value = new loop_array((array) $this->value, false);

        unset($a->_type);

        return $a;
    }
}
