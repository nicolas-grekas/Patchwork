<?php

class_exists('iaForm_hidden');

class iaForm extends loop_callAgent
{
	public $rawValues;
	public $errormsg = array();
	public $sessionLink = false;

	protected $elt = array();
	protected $hidden = array();

	protected $POST;
	protected $eltnameSuffix = '';

	protected $agentData = false;
	protected $agentPrefix = 'f_';

	protected $hasfile = false;
	protected $isOnChecked = false;
	protected $enterControl = false;
	protected $firstName = -1;

	protected $contextPool = array();
	protected $defaults = array();


	public function __construct($agentData, $sessionLink = '', $POST = true, $formVarname = 'form')
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
			$this->sessionLink =& SESSION::get($sessionLink);
			if (!$this->sessionLink) $this->sessionLink = array(0);
		}
	}

	public function setPrefix($prefix)
	{
		$this->agentPrefix = $prefix;
	}

	public function pushContext($agentData, $eltnameSuffix = '')
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

	public function pullCOntext()
	{
		list(
			$this->agentData,
			$this->agentPrefix,
			$this->eltnameSuffix,
			$this->defaults
		) = $a = array_pop($this->contextPool);
	}

	public function setDefaults($data)
	{
		$this->defaults = (array) $data;
	}

	public function add($type, $name, $param = array(), $autoPopulate = true)
	{
		if (!isset($param['default']) && isset($this->defaults[$name])) $param['default'] = $this->defaults[$name];

		$type = 'iaForm_' . preg_replace("'[^a-zA-Z\d]+'u", '_', $type);
		$elt = $this->elt[$this->agentPrefix . $name . $this->eltnameSuffix] = new $type($this, $this->agentPrefix . $name . $this->eltnameSuffix, $param, $this->sessionLink);

		if ($type=='iaForm_hidden') $this->hidden[] = $elt;
		else if ($autoPopulate && $this->agentData) $this->agentData->{$this->agentPrefix . $name} = $elt;

		return $elt;
	}

	public function getElement($name)
	{
		return $this->elt[$this->agentPrefix . $name . $this->eltnameSuffix];
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
		$this->agent = 'form/form';
		$this->keys = '[]';

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
