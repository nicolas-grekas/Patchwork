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


/* interface is out of date
class pForm_time extends pForm_text
{
    protected $maxlength = 2;
    protected $maxint = 23;
    protected $minute;

    protected function init(&$param)
    {
        $param['valid'] = 'int';
        $param[0] = 0; $param[1] = 23;
        parent::init($param);

        $this->minute = $form->add('minute', $name.'_minute', array('valid'=>'int', 0, 59));
    }

    function getValue()
    {
        return $this->status ? 60*(60*$this->value + ($this->minute->status ? $this->minute->value : 0)) : 0;
    }
}
*/
