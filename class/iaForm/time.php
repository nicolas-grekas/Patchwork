<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

/* interface is out of date
class extends iaForm_text
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

	function getValue()
	{
		return $this->status ? 60*(60*$this->value + ($this->minute->status ? $this->minute->value : 0)) : 0;
	}
}
*/
