<?php

class agent_css extends agent_bin
{
	public $argv = array('__0__');

	protected $maxage = -1;

	protected $watch = array('public/css');

	function control()
	{
		CIA::header('Content-Type: text/css; charset=UTF-8');

		$tpl = $this->argv->__0__;

		if ($tpl !== '')
		{
			$tpl = str_replace(
				array('\\', '../'),
				array('/' , '/'),
				"css/$tpl.css"
			);
		}

		$this->template = $tpl;
	}
}
