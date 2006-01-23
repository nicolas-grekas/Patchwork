<?php

class iaForm_hidden extends loop_callAgent
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

	public function __construct($form, $name, $param, &$sessionLink = false)
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

	public function &getDbValue()
	{
		return $this->getValue(true, true);
	}

	final public function &getValue($checkStatus = true, $checkIsData = false)
	{
		$v = null;

		if ($checkStatus && $this->status===false) return $v;
		if ($checkIsData && !$this->isdata) return $v;

		return $this->value;
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function add()
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

	public function isOn()
	{
		if (!$this->form->checkIsOn($this->name, $this->status)) return false;
		
		$error = array();

		foreach ($this->elt as $name => $elt)
		{
			$onempty = $elt[1];
			$onerror = $elt[2];
			$elt = $elt[0];

			$elt = $elt->checkError($onempty, $onerror);

			if ($elt)
			{
				if ($elt === true) return false;
				$error[] = $elt;
			}
		}

		$this->form->errormsg =& $error;

		if ($this->sessionLink && $error) unset($this->sessionLink[$this->name]);

		return empty($error);
	}

	public function checkError($onempty, $onerror)
	{
		if ($onempty && $this->status==='') return $this->errormsg = $onempty;
		else if ($onerror && $this->status===false) return $this->errormsg = $onerror;
		else if ($this->status===false) return true;
		else if ($this->status==='') $this->value = '';

		return false;
	}

	public function &getData()
	{
		$data  = array();

		foreach ($this->elt as $name => $elt)
		{
			if (($elt = $elt[0]->getDbValue()) !== null) $data[$name] = $elt;
		}

		return $data;
	}

	protected function init(&$param)
	{
		$this->valid = isset($param['valid']) ? $param['valid'] : 'string';

		if (isset($param['isdata'])) $this->isdata = (bool) $param['isdata'];
		if (@$param['multiple'])
		{
			$this->isdata = false;
			$this->multiple = true;
		}

		$i = 0;
		while(isset($param[$i])) $this->valid_args[] =& $param[$i++];

		if (isset($this->form->rawValues[$this->name])) $this->value = $this->form->rawValues[$this->name];
		else if (isset($param['default'])) $this->value = $param['default'];
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

						foreach ($this->value as $key => $value)
						{
							$value = VALIDATE::get($value, $this->valid, $this->valid_args);

							if ($value===false) $status = false;
							else
							{
								$this->value[$key] = $value;
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

		if ($this->eltToCheck) $a->_elements = new loop_array($this->eltToCheck, 'render_rawArray');
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

class iaForm_text extends iaForm_hidden
{
	protected $type = 'text';
	protected $maxlength = 255;

	protected function init(&$param)
	{
		parent::init($param);
		if (@$param['maxlength'] > 0) $this->maxlength = (int) $param['maxlength'];
		if (mb_strlen($this->value) > $this->maxlength) $this->value = mb_substr($this->value, 0, $this->maxlength);
	}

	protected function get()
	{
		$a = parent::get();
		if ($this->maxlength) $a->maxlength = $this->maxlength;
		return $a;
	}

	protected function addJsValidation($a)
	{
		$a->_valid = new loop_array(array_merge(array($this->valid), $this->valid_args));
		return $a;
	}
}

class iaForm_password extends iaForm_text
{
	protected $type = 'password';

	protected function get()
	{
		$this->value = '';
		return parent::get();		
	}
}

class iaForm_textarea extends iaForm_text
{
	protected $type = 'textarea';
	protected $maxlength = 65535;

	protected function get()
	{
		$a = parent::get();
		unset($a->maxlength);

		if ($this->maxlength)
		{
			$a->onkeyup = 'if(this.value.length>'.$this->maxlength.')this.value=this.value.substr(0,'.$this->maxlength.')';
		}

		return $a;
	}
}

class iaForm_select extends iaForm_hidden
{
	protected $type = 'select';
	protected $item = array();
	protected $firstItem = false;
	protected $length = -1;
	
	protected function init(&$param)
	{
		if (isset($param['firstItem'])) $this->firstItem = $param['firstItem'];

		if (isset($param['item'])) $this->item =& $param['item'];
		else if (isset($param['sql']))
		{
			$db = DB();
			$this->item = array();
			$result =& $db->query($param['sql']);

			$this->length = 0;

			while ($row =& $result->fetchRow())
			{
				if (isset($param['renderer'])) $row = call_user_func_array($param['renderer'], array(&$row));
				if ('' !== (string) @$row->G)
				{
					if (isset($this->item[ $row->G ])) $this->item[ $row->G ][ $row->K ] =& $row->V;
					else
					{
						$this->item[ $row->G ] = array($row->K => &$row->V);
						$this->length += 2;
					}
				}
				else $this->item[ $row->K ] =& $row->V;

				$this->length += 1;
			}

			$result->free();
		}

		if (!isset($param['valid']))
		{
			$param['valid'] = 'in_array';
			$param[0] = array();

			$this->length = 0;

			foreach ($this->item as $k => $v)
			{
				if (is_array($v))
				{
					$param[0] += array_keys($v);
					$this->length += count($v) - 1;
				}
				else $param[0][] = $k;

				$this->length += 1;
			}
		}

		parent::init($param);
	}
	
	protected function get()
	{
		$a = parent::get();
		
		if ($this->multiple) $a->multiple = 'multiple';

		if ($this->item || $this->firstItem !== false)
		{
			if ($this->firstItem !== false)
			{
				$a->_firstItem = true;
				$a->_firstCaption = $this->firstItem;
			}

			$a->_option = new loop_iaForm_selectOption__($this->item, $this->value, $this->length);
		}

		unset($a->value);

		return $a;
	}
}

class iaForm_check extends iaForm_select
{
	protected $type = 'check';
}

class iaForm_QSelect extends iaForm_text
{
	protected $src;

	protected function init(&$param)
	{
		parent::init($param);
		if (isset($param['src'])) $this->src = $param['src'];
		if (isset($param['lock']) && $param['lock']) $this->lock = 1;
	}

	protected function get()
	{
		$a = parent::get();

		$this->agent = 'QSelect/input';

		if (isset($this->src)) $a->_src_ = $this->src;
		if (isset($this->lock)) $a->_lock_ = $this->lock;

		return $a;
	}
}

class iaForm_jsSelect extends iaForm_select
{
	protected $src;

	protected function init(&$param)
	{
		unset($param['item']);
		unset($param['sql']);
		if (!isset($param['valid'])) $param['valid'] = 'string';

		parent::init($param);

		if (isset($param['src'])) $this->src = $param['src'];
	}

	protected function get()
	{
		$a = parent::get();

		$this->agent = 'form/jsSelect';

		if (isset($this->src)) $a->_src_ = $this->src;

		if ($this->status) $a->_value = new loop_array((array) $this->value, false);

		unset($a->_type);

		return $a;
	}
}

class iaForm_file extends iaForm_text
{
	protected $type = 'file';
	public $isfile = true;
	public $isdata = false;

	protected function init(&$param)
	{
		$this->valid_args[] = $this->maxlength = (int) @$param['maxlength'];

		$this->valid = @$param['valid'];
		if (!$this->valid) $this->valid = 'file';

		$i = 0;
		while(isset($param[$i])) $this->valid_args[] =& $param[$i++];

		$this->status = VALIDATE::getFile($_FILES[$this->name], $this->valid, $this->valid_args);
		$this->value = $this->status;
	}
	
	protected function addJsValidation($a)
	{
		$a->_valid = new loop_array(array('string', @$this->valid_args[0]));
		return $a;
	}
}

class iaForm_minute extends iaForm_text
{
	protected $maxlength = 2;
	protected $maxint = 59;
	
	protected function get()
	{
		$a = parent::get();
		$a->onchange = "this.value=this.value/1||'';if(this.value<0||this.value>{$this->maxint})this.value=''";
		return $a;
	}
}

/* interface is out of date
class iaForm_time extends iaForm_text
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

	public function getValue()
	{
		return $this->status ? 60*(60*$this->value + ($this->minute->status ? $this->minute->value : 0)) : 0;
	}
}
*/

class iaForm_date extends iaForm_text
{
	protected $maxlength = 10;
	
	protected function init(&$param)
	{
		if (!isset($param['valid'])) $param['valid'] = 'date';
		if (isset($param['default']) && '0000-00-00' == $param['default']) unset($param['default']);

		parent::init($param);
	}
	
	protected function get()
	{
		$a = parent::get();
		$a->onchange = 'this.value=valid_date(this.value)';
		return $a;
	}
	
	public function getTimestamp()
	{
		if ($v = $this->getValue())
		{
			if (preg_match("'^(\d{2})-(\d{2})-(\d{4})$'", $v, $v))
			{
				$v = mktime(0,0,0, $v[2], $v[1], $v[3]);
			}
			else $v = 0;
		}

		return (int) $v;
	}

	public function getMysqlDate()
	{
		if ($v = $this->getValue())
		{
			if (preg_match("'^(\d{2})-(\d{2})-(\d{4})$'", $v, $v))
			{
				$v = $v[3] . '-' . $v[2] . '-' . $v[1];
			}
			else $v = '';
		}

		return (string) $v;
	}

	public function getDbValue()
	{
		return $this->getMysqlDate();
	}
}

class iaForm_submit extends iaForm_hidden
{
	protected $type = 'submit';
	public $isdata = false;

	protected function init(&$param)
	{
		if (isset($this->form->rawValues[$this->name])) $this->status = true;
		else if (isset($this->form->rawValues[$this->name.'_x']) && isset($this->form->rawValues[$this->name.'_y']))
		{
			$this->value = array(@$this->form->rawValues[$this->name.'_x'], @$this->form->rawValues[$this->name.'_y']);

			$x = VALIDATE::get($this->value[0], 'int');
			$y = VALIDATE::get($this->value[1], 'int');

			$this->status = $x!==false && $y!==false;
			$this->value = $this->status ? array($x, $y) : array();
		}
		else $this->status = '';
	}

	protected function get()
	{
		$a = parent::get();
		unset($a->value);
		return $a;
	}
}

class iaForm_button extends iaForm_submit
{
	protected $type = 'button';
}

class iaForm_image extends iaForm_submit
{
	protected $type = 'image';

	protected function init(&$param)
	{
		unset($this->form->rawValues[$this->name]);
		parent::init($param);
	}
}

class loop_iaForm_selectOption__ extends loop
{
	protected $item;
	protected $value;
	protected $length;
	protected $group = false;

	public function __construct(&$item, &$value, $length)
	{
		$this->item =& $item;
		$this->value = array_flip((array) $value);
		$this->length = $length;
	}

	protected function prepare()
	{
		reset($this->item);

		if ($this->length >= 0) return $this->length;
		
		$this->length = 0;
		foreach ($this->item as $k => $v)
		{
			if (is_array($v)) $this->length += count($v) - 1;
			$this->length += 1;
		}

		return $this->length;
	}

	protected function next()
	{
		if (is_array($this->group))
		{
			if (!(list($key, $caption) = each($this->group)))
			{
				$this->group = false;
				return (object) array('_groupOff' => 1);
			}
		}
		else
		{
			if (!(list($key, $caption) = each($this->item))) return false;

			if (is_array($caption))
			{
				reset($caption);
				$this->group =& $caption;
				return (object) array(
					'_groupOn' => 1,
					'label' => $key
				);
			}
		}

		if (is_object($caption))
		{
				$a = $caption;
				$a->value = $key;
		}
		else $a = (object) array(
			'value' => $key,
			'caption' => $caption
		);

		if (isset($this->value[(string) $key]))
		{
			$a->selected = 'selected';
			$a->checked = 'checked';
		}

		return $a;
	}
}
