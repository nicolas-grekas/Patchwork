<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class agent_jsSelect extends agent
{
    const contentType = 'text/javascript';

    protected

    $maxage = -1,
    $template = 'form/jsSelect.js',

    $param = array();


    function compose($o)
    {
        unset($this->param['valid']);
        unset($this->param['firstItem']);
        unset($this->param['multiple']);

        $this->form = new pForm($o, '', true, '');
        $this->form->add('select', 'select', $this->param);

        return $o;
    }
}
