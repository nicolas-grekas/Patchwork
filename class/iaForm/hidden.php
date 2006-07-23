<?php

class extends loop_callAgent
{
	protected $name = '';
	protected $value = '';
	protected $status = false;

	protected $isfile = false;
	protected $isdata = true;
	protected $mandatory = false;
	protected $errormsg = '';

	protected $form;
	protected $sessionLink = false;

	protected $multiple = false;
	protected $type = 'hidden';

	protected $valid;
	protected $valid_args = array();

	protected $elt = array();
	protected $eltToCheck = array();
	protected $isOn;

	function __construct($form, $name, $param, &$sessionLink = false)
	{
		$this->form = $form;
		$this->sessionLink =& $sessionLink;
		$this->name =& $name;

		$this->init($param);

		if ($sessionLink)
		{
			if (!isset($this->form->rawValues[$name]) && isset($sessionLink[$name])) $this->value =& $sessionLink[$name];
			else $sessionLink[$name] =& $this->value;
		}

		$form->setFile($this->isfile);
	}

	final public function getName() {return $this->name;}

	final public function setValue($value)
	{
		$this->value = $value;
	}

	function getDbValue()
	{
		$a = $this->value;

		if ($this->isdata && is_array($a))
		{
			$b = '';
			foreach ($a as &$v) $b .= ',' . str_replace(array('%', ','), array('%25', '%2C'), $v);
			$a = substr($b, 1);
		}

		return $a;
	}

	final public function isValidData($checkStatus = true, $checkIsData = true)
	{
		if ($checkStatus && $this->status===false) return false;
		if ($checkIsData && !$this->isdata) return false;

		return true;
	}

	final public function getValue()
	{
		return $this->value;
	}

	function getStatus()
	{
		return $this->status;
	}

	function add()
	{
		$a = func_get_args();

		$len = count($a);
		for ($i = 0; $i<$len; $i+=3)
		{
			$name = $a[$i];
			$onempty = $a[$i+1];
			$onerror = $a[$i+2];

			$addedElt = $this->form->getElement($name);
			$this->elt[$name] = array(
				$addedElt,
				$onempty,
				$onerror
			);

			if ($onempty || $onerror)
			{
				$this->eltToCheck[] = array(
					'name' => $addedElt->getName(),
					'onempty' => $onempty,
					'onerror' => $onerror
				);
			}

			if ($onempty) $this->elt[$name][0]->mandatory = true;
		}
	}

	function isOn()
	{
		if (isset($this->isOn)) return $this->isOn;
		if ($this->status === '') return $this->isOn = false;

		$error =& $this->form->errormsg;

		foreach ($this->elt as &$elt_info)
		{
			$onempty = $elt_info[1];
			$onerror = $elt_info[2];
			$elt = $elt_info[0];

			$elt = $elt->checkError($onempty, $onerror);

			if ($elt && $elt !== true) $error[] = $elt;
		}

		if ($this->sessionLink && $error) unset($this->sessionLink[$this->name]);

		return $this->isOn = empty($error);
	}

	function checkError($onempty, $onerror)
	{
		if ($this->errormsg) return true;
		else if ($onempty && $this->status==='') return $this->errormsg = $onempty;
		else if ($onerror && $this->status===false) return $this->errormsg = $onerror;
		else if ($this->status===false)
		{
			if (DEBUG)
			{
				E('Input validation error in ' . get_class($this) . ' element: ' . print_r(array(
					'name' => $this->name,
					'value' => $this->value,
					'valid' => $this->valid, $this->valid_args
				), true));
			}

			return "Input validation error in field {$this->name}";
		}
		else if ($this->status==='') $this->value = '';

		return false;
	}

	function setError($message)
	{
		$this->status = false;
		$this->form->errormsg[] = $this->errormsg = $message;
	}

	function &getData()
	{
		$data  = array();

		foreach ($this->elt as $name => &$elt) if ($elt[0]->isValidData()) $data[$name] = $elt[0]->getDbValue();

		return $data;
	}

	protected function init(&$param)
	{
		$this->valid = isset($param['valid']) ? $param['valid'] : 'string';

		if (@$param['multiple'])
		{
			$this->isdata = false;
			$this->multiple = true;
		}

		if (isset($param['isdata'])) $this->isdata = (bool) $param['isdata'];

		$i = 0;
		while(isset($param[$i])) $this->valid_args[] =& $param[$i++];

		if (isset($this->form->rawValues[$this->name])) $this->value = $this->form->rawValues[$this->name];
		else if (isset($param['default']))
		{
			$this->value = $param['default'];

			if ($this->multiple && !is_array($this->value))
			{
				$this->value = explode(',', $this->value);
				$this->value = array_map('rawurldecode', $this->value);
			}
		}
		else $this->value = '';

		if ($this->multiple)
		{
			$this->status = '';

			if ($this->value)
			{
				if (is_array($this->value))
				{
					if (implode('', $this->value)==='') $this->status = '';
					else
					{
						$status = true;

						foreach ($this->value as &$value)
						{
							$v = VALIDATE::get($value, $this->valid, $this->valid_args);

							if ($v===false) $status = false;
							else
							{
								$value = $v;
								$status = true && $status;
							}
						}

						$this->status = $status;
					}
				}
				else
				{
					$this->status = false;
					$this->value = array();
				}
			}
			else $this->value = array();
		}
		else if ((string) $this->value==='') $this->status = '';
		else
		{
			$this->status = VALIDATE::get($this->value, $this->valid, $this->valid_args);

			if ($this->status!=='' && $this->status!==false)
			{
				$this->value = $this->status;
				$this->status = true;
			}
		}
	}

	protected function get()
	{
		$this->agent = 'form/input';
		$this->keys = '[]';

		$a = (object) array(
			'_type' => $this->type,
			'name' => $this->name . ($this->multiple ? '[]' : ''),
		);

		if ($this->eltToCheck) $a->_elements = new loop_array($this->eltToCheck, 'filter_rawArray');
		if (!$this->multiple) $a->value = $this->value;
		if ($this->errormsg) $a->_errormsg = $this->errormsg;
		if ($this->mandatory) $a->_mandatory = 1;

		return $this->addJsValidation($a);
	}

	protected function addJsValidation($a)
	{
		return $a;
	}
}
