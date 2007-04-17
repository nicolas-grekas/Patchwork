<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends loop_callAgent
{
	public $rawValues;
	public $errormsg = array();
	public $sessionLink = false;
	public $action = false;

	protected $elt = array();
	protected $hidden = array();

	protected $POST;
	protected $eltnameSuffix = '';

	protected $agentData = false;
	protected $agentPrefix = 'f_';

	protected $hasfile = false;
	protected $enterControl = false;
	protected $firstName = -1;

	protected $contextPool = array();
	protected $defaults = array();

	function __construct($agentData, $sessionLink = '', $POST = true, $formVarname = 'form')
	{
		if ($agentData)
		{
			if ($formVarname) $agentData->$formVarname = $this;
			$this->agentData = $agentData;
		}
		else $this->agentData = false;

		$this->POST = (bool) $POST;
		if ($this->POST)
		{
			CIA::canPost();
			$this->rawValues =& $_POST;
		}
		else $this->rawValues =& $_GET;

		if ($sessionLink)
		{
			SESSION::bind($sessionLink, $this->sessionLink);
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
		$this->defaults = (array) $data;
	}

	function add($type, $name, $param = array(), $autoPopulate = true)
	{
		if (!isset($param['default']) && isset($this->defaults[$name])) $param['default'] = $this->defaults[$name];

		$type = 'iaForm_' . preg_replace('"[^a-zA-Z0-9\x80-\xff]+"', '_', $type);
		$elt = $this->elt[$this->agentPrefix . $name . $this->eltnameSuffix] = new $type($this, $this->agentPrefix . $name . $this->eltnameSuffix, $param, $this->sessionLink);

		if ($type=='iaForm_hidden') $this->hidden[] = $elt;
		else if ($autoPopulate && $this->agentData) $this->agentData->{$this->agentPrefix . $name} = $elt;

		return $elt;
	}

	function setError($eltname, $message)
	{
		$this->getElement($eltname)->setError($message);
	}

	function getElement($name)
	{
		return $this->elt[$this->agentPrefix . $name . $this->eltnameSuffix];
	}

	function setFile($isfile)
	{
		if ($isfile && !$this->hasfile)
		{
			$this->hasfile = true;

			if (function_exists('upload_progress_meter_get_info') || function_exists('uploadprogress_get_info'))
			{
				$elt = $this->elt['UPLOAD_IDENTIFIER'] = new iaForm_hidden($this, 'UPLOAD_IDENTIFIER', array(), $this->sessionLink);
				$elt->setValue(CIA::uniqid());
				array_unshift($this->hidden, $elt);
			}
		}
	}

	function setEnterControl($name = '')
	{
		if ($this->firstName === -1) $this->firstName = $name;
		else if ($name != $this->firstName) $this->enterControl = true;
	}

	protected function get()
	{
		$this->agent = 'form/form';
		$this->keys = '[]';

		$a = (object) array(
			'_hidden' => new iaForm_hiddenLoop__($this->hidden),
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

class iaForm_hiddenLoop__ extends loop
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
