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


class pForm_fckeditor extends pForm_textarea
{
    protected

    $toolbarSet,
    $config;


    protected function init(&$param)
    {
        if (isset($this->form->rawValues[$this->name]))
        {
            $value =& $this->form->rawValues[$this->name];
            $value = FILTER::get($value, 'html');
        }

        parent::init($param);

        if (isset($param['toolbarSet'])) $this->toolbarSet = $param['toolbarSet'];
        if (isset($param['config'])) $this->config = $param['config'];
    }

    protected function get()
    {
        $a = parent::get();

        $this->agent = 'form/fckeditor';

        if (isset($this->toolbarSet)) $a->_toolbarSet = $this->toolbarSet;
        if (isset($this->config)) $a->_config = $this->config;

        return $a;
    }
}
