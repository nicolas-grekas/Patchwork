<?php

class_exists('iaForm_hidden');

class iaForm extends loop_singleBlock
{
	public $rawValues;
	public $errormsg = array();
	public $sessionLink = false;

	protected $elt = array();
	protected $hidden = array();

	protected $POST;
	protected $eltnameSuffix;

	protected $agentData = false;
	protected $agentPrefix = 'f_';

	protected $hasfile = false;
	protected $isOnChecked = false;
	protected $enterControl = false;
	protected $firstName = -1;


	public function __construct($sessionLink = '', $eltnameSuffix = '', $POST = true)
	{
		$this->eltnameSuffix = $eltnameSuffix;

		$this->POST = (bool) $POST;
		if ($this->POST) $this->rawValues =& $_POST;
		else $this->rawValues =& $_GET;

		if ($sessionLink)
		{
			$this->sessionLink =& SESSION::get($sessionLink);
			if (!$this->sessionLink) $this->sessionLink = array(0);
		}
	}

	public function autoPopulate($agentData, $formVarname = 'form', $prefix = false)
	{
		if ($agentData)
		{
			if ($prefix !== false) $this->agentPrefix = $prefix;
			if ($formVarname) $agentData->$formVarname = $this;
			$this->agentData = $agentData;
		}
		else $this->agentData = false;
	}

	public function setPrefix($prefix)
	{
		$this->agentPrefix = $prefix;
	}

	public function add($type, $name, $param = array(), $autoPopulate = true)
	{
		$type = 'iaForm_' . preg_replace("'[^a-zA-Z\d]+'u", '_', $type);
		$elt = $this->elt[$name] = new $type($this, $this->agentPrefix . $name . $this->eltnameSuffix, $param, $this->sessionLink);

		if ($type=='iaForm_hidden') $this->hidden[] = $elt;
		else if ($autoPopulate && $this->agentData) $this->agentData->{$this->agentPrefix . $name} = $elt;

		return $elt;
	}

	public function getElement($name)
	{
		return $this->elt[$name];
	}

	public function setFile($isfile)
	{
		if ($isfile && !$this->hasfile)
		{
			$this->hasfile = true;

			if (is_callable('upload_progress_meter_get_info'))
			{
				$elt = $this->elt['UPLOAD_IDENTIFIER'] = new iaForm_hidden($this, 'UPLOAD_IDENTIFIER', array(), $this->sessionLink);
				$elt->setValue(CIA::uniqid());
				array_unshift($this->hidden, $elt);
			}
		}
	}

	public function checkIsOn($name, $status)
	{
		if ($this->firstName === -1) $this->firstName = $name;
		else if ($name != $this->firstName) $this->enterControl = true;

		if ($this->isOnChecked) return false;
		if ($status === '') return false;

		$this->isOnChecked = true;

		return true;
	}

	protected function get()
	{
		$a = (object) array(
			'_hidden' => new iaForm_hiddenLoop__($this->hidden),
			'_errormsg' => new loop_array($this->errormsg)
		);

		if ($this->POST) $a->method = 'POST';
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

	public function __construct(&$array) {$this->array =& $array;}
	protected function prepare() {return count($this->array);}
	protected function next()
	{
		if (list(, $value) = each($this->array))
		{
			$result = $value->render();
			$value->render();
			return $result;
		}
		else
		{
			reset($this->array);
			return false;
		}
	}
}
