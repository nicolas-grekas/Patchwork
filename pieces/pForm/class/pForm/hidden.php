<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pForm_hidden extends loop_agentWrapper
{
    protected

    $name = '',
    $value = '',
    $status = false,
    $disabled = false,
    $readonly = false,

    $isfile = false,
    $isdata = true,
    $required = false,
    $validmsg = '',
    $errormsg = '',

    $form,
    $sessionLink = false,

    $multiple = false,
    $type = 'hidden',

    $valid,
    $validArgs = array(),
    $validDefaultRx  = '',
    $validDefaultMsg = '',

    $elements = array(),
    $elementsToCheck = array(),
    $isOn;


    function __construct($form, $name, $param, &$sessionLink = false)
    {
        empty($form) && user_error(get_class($this) . ': $form parameter is empty');

        $this->form = $form;
        $this->sessionLink =& $sessionLink;
        $this->name =& $name;

        is_array($param) || $param = (array) $param;
        $this->init($param);

        if ($sessionLink)
        {
            if (isset($sessionLink[$name]) && $this->readonly || !isset($this->form->rawValues[$name]))
            {
                $this->value =& $sessionLink[$name];
                $this->status = '';
            }
            else $sessionLink[$name] =& $this->value;
        }

        $form->setFile($this->isfile);
    }

    function getName()
    {
        return $this->name;
    }

    function setValue($value)
    {
        true === $value && $value = 1;
        false === $value && $value = 0;
        $this->value = $value;
    }

    function getDbValue()
    {
        $a = $this->value;

        if ($this->isdata && is_array($a))
        {
            if ($a)
            {
                $b = '';
                foreach ($a as &$v) $b .= ',' . strtr($v, array('%' => '%25', ',' => '%2C'));
                $a = substr($b, 1);
            }
            else $a = '';
        }

        return $a;
    }

    function isValidData($checkStatus = true, $checkIsData = true)
    {
        if ($checkStatus && false === $this->status) return false;
        if ($checkIsData && !$this->isdata) return false;

        return true;
    }

    function getValue()
    {
        return $this->value;
    }

    function getStatus()
    {
        return $this->status;
    }

    function attach()
    {
        $a = func_get_args();
        $elements = array();

        $len = count($a);
        for ($i = 0; $i<$len; $i+=3)
        {
            $name = $a[$i];
            $onempty = $a[$i+1];
            $onerror = $a[$i+2];

            $addedElt = $this->form->getElement($name);

            if (!$addedElt)
            {
/**/            if (DEBUG)
                    user_error("Form's element does not exists: {$name}");
                continue;
            }

            $onerror || $onerror = $addedElt->validmsg;

            $elements[$name] = $this->elements[$name] = array(
                $addedElt,
                $onempty,
                $onerror,
            );

            if ($onempty || $onerror)
            {
                $name = $addedElt->getName();

                $this->elementsToCheck[$name] = array(
                    'name' => $name,
                    'onempty' => $onempty,
                    'onerror' => $onerror,
                );
            }
            else unset($this->elementsToCheck[$name]);

            $addedElt->required || $addedElt->required = (bool) $onempty;
        }

        if (isset($this->isOn) && $elements)
        {
            $this->isOn =& $elements;
            $this->isOn();
        }
    }

    function isOn()
    {
        if ($this->disabled) return false;

        $elements =& $this->elements;

        if (isset($this->isOn))
        {
            if (is_array($this->isOn)) $elements =& $this->isOn;
            else return $this->isOn;
        }

        if ('' === $this->status || isset($GLOBALS['_POST_BACKUP']) && $this->form->isPOST()) return $this->isOn = false;

        $error = array();

        foreach ($elements as $elt)
        {
            $onempty = $elt[1];
            $onerror = $elt[2];
            $elt     = $elt[0];

            $elt = $elt->checkError($onempty, $onerror);

            if ($elt && true !== $elt) $this->form->errormsg[] = $error[] = $elt;
        }

        if ($this->sessionLink && $error) unset($this->sessionLink[$this->name]);

        return $this->isOn = empty($error);
    }

    protected function checkError($onempty, $onerror)
    {
        if ($this->errormsg) return true;

        if ($onempty && '' === $this->status && !$this->readonly)
        {
/**/        if (DEBUG)
/**/        {
                if (  $this->isfile
                    ? !isset($this->form->filesValues[$this->name])
                    : !isset($this->form->rawValues[$this->name]))
                {
                    user_error("Form's input data do not even mention the [{$this->name}] mandatory field .\nMaybe it is not present in the definition of the form ?");
                }
/**/        }

            return $this->errormsg = $onempty;
        }
        else if (false === $this->status)
        {
            if ($onerror) {}
            else if ($this->validmsg) $onerror = $this->validmsg;
            else
            {
/**/            if (DEBUG)
/**/            {
                    user_error('Input validation error in ' . get_class($this) . ' element: ' . print_r(array(
                        'name' => $this->name,
                        'value' => $this->value,
                        'valid' => $this->valid, $this->validArgs
                    ), true));
/**/            }

                if ($this->isfile && isset($this->form->filesValues[$this->name]['error']))
                {
                    $a = $this->form->filesValues[$this->name]['error'];
                    is_array($a) && $a = $a[0];

                    switch ($a)
                    {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $onerror = T('The uploaded file size exceeds the allowed limit');
                        break;

                    case UPLOAD_ERR_PARTIAL:
                        $onerror = T('The uploaded file was only partially uploaded');
                        break;

                    default:
                        $onerror = sprintf(T('File upload failed (code #%d)'), $this->name, $a);
                        break;
                    }
                }
                else $onerror = T('Input validation error');
            }

            return $this->errormsg = $onerror;
        }
        else if ('' === $this->status) $this->value = $this->multiple ? array() : '';

        return false;
    }

    function setError($message = '')
    {
        $message || $message = $this->validmsg;
        $message || user_error(get_class($this) . ': empty error $message');
        $this->status = false;
        $this->form->errormsg[] = $this->errormsg = $message;
    }

    function &getData()
    {
        $data  = array();

        foreach ($this->elements as $name => &$elt) if ($elt[0]->isValidData()) $data[$name] = $elt[0]->getDbValue();

        return $data;
    }

    protected function init(&$param)
    {
        empty($param['disabled']) || $this->disabled = true;
        if ($this->disabled || !empty($param['readonly'])) $this->readonly = true;

        if (isset($param['valid']))
        {
            $this->valid = $param['valid'];
        }
        else if (!isset($this->valid))
        {
            $this->valid = 'char';

            if (!isset($param[0]) && '' !== $this->validDefaultRx)
            {
                $this->validArgs[] = $this->validDefaultRx;
                $this->validmsg = T($this->validDefaultMsg);
            }
        }

        if (!empty($param['multiple']))
        {
            $this->isdata = false;
            $this->multiple = true;
        }

        isset($param['isdata']) && $this->isdata = (bool) $param['isdata'];

        $i = 0;
        while(isset($param[$i])) $this->validArgs[] =& $param[$i++];

        isset($param['validmsg']) && $this->validmsg = $param['validmsg'];
        isset($param['validMsg']) && $this->validmsg = $param['validMsg'];
        $this->validmsg || $this->validmsg = FILTER::getMsg($this->valid, $this->validArgs);

        if (!$this->readonly && isset($this->form->rawValues[$this->name]))
        {
            $value = $this->form->rawValues[$this->name];

            if (is_string($value) && false !== strpos($value, "\0"))
            {
                $value = str_replace("\0", '', $value);
                $this->form->rawValues[$this->name] = $value;
            }
        }
        else if (isset($param['default']))
        {
            $value = $param['default'];

            if ($this->multiple && !is_array($value))
            {
                $value = explode(',', $value);
                $value = array_map('rawurldecode', $value);
            }

            $this->setValue($value);
            $value =& $this->value;
        }
        else $value = '';

        if ($this->multiple)
        {
            $this->status = '';

            if ($value)
            {
                if (is_array($value))
                {
                    $status = true;

                    foreach ($value as $i => &$v)
                    {
                        if ('' === $v) unset($value[$i]);
                        else
                        {
                            $a = FILTER::get($v, $this->valid, $this->validArgs);

                            if (false === $a) $status = false;
                            else
                            {
                                $v = $a;
                                $status = true && $status;
                            }
                        }
                    }

                    $value && $this->status = $status;
                }
                else
                {
                    $this->status = false;
                    $value = array();
                }
            }
            else $value = array();
        }
        else if ('' === (string) $value) $this->status = '';
        else
        {
            $this->status = FILTER::get($value, $this->valid, $this->validArgs);

            if ('' !== $this->status && false !== $this->status)
            {
                $value = $this->status;
                $this->status = true;
            }
        }

        $this->setValue($value);
    }

    protected function get()
    {
        $this->agent = 'form/input';
        $this->keys = '';

        $a = (object) array(
            '_type' => $this->type,
            'name' => $this->name . ($this->multiple ? '[]' : ''),
        );

        $this->multiple || $a->value = $this->value;
        $this->elementsToCheck && $a->_elements = new loop_array($this->elementsToCheck, 'filter_rawArray');

        $this->validmsg && $a->_validmsg = $this->validmsg;
        $this->errormsg && $a->_errormsg = $this->errormsg;
        $this->required && $a->required = 'required';

        if ($this->disabled) $a->disabled = 'disabled';
        else if ($this->readonly) $a->readonly = 'readonly';

        return $this->addJsValidation($a);
    }

    protected function addJsValidation($a)
    {
        return $a;
    }
}
