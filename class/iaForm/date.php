<?php

class extends iaForm_text
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

	function getDbValue()
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
}
