<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class extends loop_agentWrapper
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
	$errormsg = '',

	$form,
	$sessionLink = false,

	$multiple = false,
	$type = 'hidden',

	$valid,
	$valid_args = array(),

	$elt = array(),
	$eltToCheck = array(),
	$isOn;


	function __construct($form, $name, $param, &$sessionLink = false)
	{
		$this->form = $form;
		$this->sessionLink =& $sessionLink;
		$this->name =& $name;

		is_array($param) || $param = (array) $param;
		$this->init($param);

		if ($sessionLink)
		{
			if (isset($sessionLink[$name]) && $this->readonly || !isset($this->form->rawValues[$name])) $this->value =& $sessionLink[$name];
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

	final public function isValidData($checkStatus = true, $checkIsData = true)
	{
		if ($checkStatus && false === $this->status) return false;
		if ($checkIsData && !$this->isdata) return false;

		return true;
	}

	final public function getValue()
	{
		return $this->value;
	}

	function getStatus()
	{
		return $this->disabled ? '' : $this->status;
	}

	function attach()
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

			if ($onempty) $this->elt[$name][0]->required = true;
		}
	}

	function isOn()
	{
		if ($this->disabled) return false;

		if (isset($this->isOn)) return $this->isOn;
		if ('' === $this->status || isset($GLOBALS['_POST_BACKUP'])) return $this->isOn = false;

		$error =& $this->form->errormsg;

		foreach ($this->elt as &$elt_info)
		{
			$onempty = $elt_info[1];
			$onerror = $elt_info[2];
			$elt = $elt_info[0];

			$elt = $elt->checkError($onempty, $onerror);

			if ($elt && true !== $elt) $error[] = $elt;
		}

		if ($this->sessionLink && $error) unset($this->sessionLink[$this->name]);

		return $this->isOn = empty($error);
	}

	function checkError($onempty, $onerror)
	{
		if ($this->errormsg) return true;
		else if ($onempty && '' === $this->status) return $this->errormsg = $onempty;
		else if ($onerror && false === $this->status) return $this->errormsg = $onerror;
		else if (false === $this->status)
		{
/*<
			W('Input validation error in ' . get_class($this) . ' element: ' . print_r(array(
				'name' => $this->name,
				'value' => $this->value,
				'valid' => $this->valid, $this->valid_args
			), true));
>*/

			$a =& $this->form->filesValues;

			if ($this->isfile && isset($a[$this->name]))
			{
				switch ($a[$this->name]['error'])
				{
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$onerror = T('The uploaded file size exceeds the allowed limit');
					break;

				case UPLOAD_ERR_PARTIAL:
					$onerror = T('The uploaded file was only partially uploaded');
					break;

				default:
					$onerror = sprintf(T('File upload failed (code #%d)'), $this->name, $a[$this->name]['error']);
					break;
				}
			}
			else $onerror = T('Input validation error');

			return $this->errormsg = $onerror;
		}
		else if ('' === $this->status) $this->value = '';

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
		empty($param['disabled']) || $this->disabled = true;
		if ($this->disabled || !empty($param['readonly'])) $this->readonly = true;

		$this->valid = isset($param['valid']) ? $param['valid'] : 'char';

		if (!empty($param['multiple']))
		{
			$this->isdata = false;
			$this->multiple = true;
		}

		if (isset($param['isdata'])) $this->isdata = (bool) $param['isdata'];

		$i = 0;
		while(isset($param[$i])) $this->valid_args[] =& $param[$i++];

		if (!$this->readonly && isset($this->form->rawValues[$this->name]))
		{
			$this->value = $this->form->rawValues[$this->name];

			if (is_string($this->value) && false !== strpos($this->value, "\0"))
			{
				$this->value = str_replace("\0", '', $this->value);
				$this->form->rawValues[$this->name] = $this->value;
			}
		}
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
					$status = true;

					foreach ($this->value as $i => &$value)
					{
						if ('' === $value) unset($this->value[$i]);
						else
						{
							$v = FILTER::get($value, $this->valid, $this->valid_args);

							if (false === $v) $status = false;
							else
							{
								$value = $v;
								$status = true && $status;
							}
						}
					}

					$this->value && $this->status = $status;
				}
				else
				{
					$this->status = false;
					$this->value = array();
				}
			}
			else $this->value = array();
		}
		else if ('' === (string) $this->value) $this->status = '';
		else
		{
			$this->status = FILTER::get($this->value, $this->valid, $this->valid_args);

			if ('' !== $this->status && false !== $this->status)
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
		if ($this->required) $a->required = 'required';

		if ($this->disabled) $a->disabled = 'disabled';
		else if ($this->readonly) $a->readonly = 'readonly';

		return $this->addJsValidation($a);
	}

	protected function addJsValidation($a)
	{
		return $a;
	}
}
