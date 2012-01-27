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


class pForm_file extends pForm_text
{
    protected

    $type = 'file',
    $isfile = true,
    $isdata = false;


    protected function init(&$param)
    {
        empty($param['disabled']) || $this->disabled = true;
        if ($this->disabled || !empty($param['readonly'])) $this->readonly = true;

        $this->validArgs[] = $this->maxlength = isset($param['maxlength']) ? (int) $param['maxlength'] : 0;

        $this->valid = isset($param['valid']) ? $param['valid'] : '';
        if (!$this->valid) $this->valid = 'file';

        $i = 0;
        while(isset($param[$i])) $this->validArgs[] =& $param[$i++];

        if (!empty($param['multiple']))
        {
            $this->multiple = true;
            $this->value = array();
        }

        if (!$this->readonly)
        {
            if ($this->multiple)
            {
                $this->status = '';
                $value = isset($this->form->filesValues[$this->name]) ? $this->form->filesValues[$this->name] : '';
                
                if (!empty($value['name']))
                {
                    if (is_array($value['name']))
                    {
                        $status = true;

                        foreach ($value['name'] as $i => $v)
                        {
                            $v = array(
                                'name'     => $v,
                                'type'     => $value['type'    ][$i],
                                'tmp_name' => $value['tmp_name'][$i],
                                'error'    => $value['error'   ][$i],
                                'size'     => $value['size'    ][$i],
                            );

                            $v = FILTER::getFile($v, $this->valid, $this->validArgs);

                            if (false === $v) $status = false;
                            else if ('' !== $v)
                            {
                                $this->value[] = $v;
                                $status = true && $status;
                            }
                        }

                        $this->value && $this->status = $status;
                    }
                    else $this->status = false;
                }
            }
            else
            {
                $this->status = FILTER::getFile($this->form->filesValues[$this->name], $this->valid, $this->validArgs);
                $this->value = $this->status;
            }
        }
        else $this->status = '';
    }

    protected function addJsValidation($a)
    {
        $a->_valid = new loop_array(array('char', isset($this->validArgs[1]) ? $this->validArgs[1] : ''));
        return $a;
    }
}
