<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork as p;
use SESSION   as s;

class pForm extends loop_agentWrapper
{
    public

    $rawValues,
    $filesValues = array(),
    $errormsg = array(),
    $sessionLink = false,
    $action = false;


    protected

    $elt = array(),
    $hidden = array(),
    $POST,
    $eltnameSuffix = '',

    $agentData = false,
    $agentPrefix = 'f_',

    $hasfile = false,
    $enterControl = false,
    $firstName = -1,

    $contextPool = array(),
    $defaults = array();


    function __construct($agentData, $sessionLink = '', $POST = true, $formVarname = 'form')
    {
        if ($agentData)
        {
            if ($formVarname)
            {
                if (isset($agentData->$formVarname))
                {
                    user_error(__CLASS__ . ": Overwriting existing \$agentData->{$formVarname}! If this is the intended behavior, unset(\$agentData->{$formVarname}) to remove this warning.");
                }

                $agentData->$formVarname = $this;
            }

            $this->agentData = $agentData;
        }
        else $this->agentData = false;

        $this->POST = (bool) $POST;
        if ($this->POST)
        {
            p::canPost();

            if (isset($_POST['_POST_BACKUP']))
            {
                // This should only be used for field persistence, not as valid input
                $this->rawValues   =& $GLOBALS['_POST_BACKUP'];
//              $this->filesValues =& $GLOBALS['_FILES_BACKUP'];
            }
            else
            {
                $this->rawValues   =& $_POST;
                $this->filesValues =& $_FILES;
            }
        }
        else $this->rawValues =& $_GET;

        if ($sessionLink)
        {
            s::bind($sessionLink, $this->sessionLink);
            if (!$this->sessionLink) $this->sessionLink = array(0);
        }
    }

    function setPrefix($prefix)
    {
        $this->agentPrefix = $prefix;
    }

    function pushContext($agentData, $eltnameSuffix = '')
    {
        $this->contextPool[] = array(
            $this->agentData,
            $this->agentPrefix,
            $this->eltnameSuffix,
            $this->defaults
        );

        if ($agentData)
        {
            $this->agentData = $agentData;
            $this->eltnameSuffix .= '_' . $eltnameSuffix;
        }
        else $this->agentData = false;

        $this->defaults = array();
    }

    function pullContext()
    {
        list(
            $this->agentData,
            $this->agentPrefix,
            $this->eltnameSuffix,
            $this->defaults
        ) = array_pop($this->contextPool);
    }

    function setDefaults($data)
    {
        $this->defaults = array_merge($this->defaults, (array) $data);
    }

    function add($type, $name, $param = array(), $autoPopulate = true)
    {
        is_array($param) || $param = (array) $param;

        if (!isset($param['default']) && isset($this->defaults[$name])) $param['default'] = $this->defaults[$name];

        $fullname = $this->agentPrefix . $name . $this->eltnameSuffix;
        $type = 'pForm_' . preg_replace('"[^a-zA-Z0-9\x80-\xFF]+"', '_', $type);
        $elt = $this->elt[$fullname] = new $type($this, $fullname, $param, $this->sessionLink);

        if ($autoPopulate && $this->agentData)
        {
            if ('pForm_hidden' == $type)
            {
                $this->hidden[$fullname] = $elt;
                unset($this->agentData->{$this->agentPrefix . $name});
            }
            else
            {
                unset($this->hidden[$fullname]);
                $this->agentData->{$this->agentPrefix . $name} = $elt;
            }
        }
        else if ('pForm_hidden' == $type)
        {
            $this->hidden[$fullname] = $elt;
        }

        return $elt;
    }

    function setError($eltname, $message)
    {
        $this->getElement($eltname)->setError($message);
    }

    function getElement($name)
    {
        $name = $this->agentPrefix . $name . $this->eltnameSuffix;
        return isset($this->elt[$name]) ? $this->elt[$name] : false;
    }

    function setFile($isfile)
    {
        if ($isfile && !$this->hasfile)
        {
            $this->hasfile = true;

            if (function_exists('upload_progress_meter_get_info') || function_exists('uploadprogress_get_info'))
            {
                $elt = $this->elt['UPLOAD_IDENTIFIER'] = new pForm_hidden($this, 'UPLOAD_IDENTIFIER', array(), $this->sessionLink);
                $elt->setValue(p::uniqId());
                array_unshift($this->hidden, $elt);
            }
        }
    }

    function setEnterControl($name = '')
    {
        if ($this->firstName === -1) $this->firstName = $name;
        else if ($name != $this->firstName) $this->enterControl = true;
    }

    function isPOST()
    {
        return $this->POST;
    }

    protected function get()
    {
        $this->agent = 'form/form';
        $this->keys = '';

        $a = (object) array(
            '_hidden' => new pForm_hiddenLoop__($this->hidden),
            '_errormsg' => new loop_array($this->errormsg)
        );

        if ($this->POST) $a->method = 'post';
        if ($this->action) $a->action = $this->action;
        if ($this->enterControl) $a->_enterControl_ = 1;
        if ($this->hasfile)
        {
            $a->enctype = 'multipart/form-data';
            if (isset($this->elt['UPLOAD_IDENTIFIER'])) $a->_upload = 1;
        }

        return $a;
    }
}

class pForm_hiddenLoop__ extends loop
{
    protected $array;

    function __construct(&$array) {$this->array =& $array;}
    protected function prepare() {return count($this->array);}
    protected function next()
    {
        if (list(, $value) = each($this->array))
        {
            $result = $value->loop();
            $value->loop();
            return $result;
        }
        else
        {
            reset($this->array);
            return false;
        }
    }
}
