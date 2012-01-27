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


class pForm_QSelect extends pForm_text
{
    protected

    $src = '',
    $lock = 0,
    $textarea = 0;


    protected function init(&$param)
    {
        isset($param['src'])  && $this->src  = $param['src'];
        empty($param['lock']) || $this->lock = 1;

        if (isset($param['textarea']))
        {
            $this->textarea = (int) (bool) $param['textarea'];
        }
        else if (isset($param['valid']) && 'text' === strtolower($param['valid'])) $this->textarea = 1;

        if ($this->textarea)
        {
            $this->maxlength = 65635;
            isset($param['valid']) || $param['valid'] = 'text';
        }

        parent::init($param);
    }

    protected function get()
    {
        $a = parent::get();

        $this->agent = 'QSelect/input';

        $a->_src = $this->src;
        $this->textarea && $a->_textarea = 1;
        $this->lock     && $a->_lock     = 1;

        return $a;
    }
}
