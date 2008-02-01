<?php

class extends agent_pStudioWidget_reader
{
	protected

	$extension,
	$language = 'php',
	$template = 'pStudioWidget/reader/php';


	function control()
	{
		if (!isset($this->extension))
		{
			$this->extension = get_class($this);
			$this->extension = strrchr($this->extension, '_');
			$this->extension = substr($this->extension, 1);
		}

		$this->get->__0__ = $this->extension . '/' . $this->get->__0__;

		parent::control();
	}

	function compose($o)
	{
		$o = parent::compose($o);

		if (isset($o->data) && $o->data)
		{
			$geshi = new geshi($o->data, $this->language);
			$o->data = $geshi->parse_code();
		}

		return $o;
	}
}
