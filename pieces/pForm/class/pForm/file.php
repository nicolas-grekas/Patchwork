<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


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
