<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class extends agent_bin
{
	protected $template = 'QSelect/Search.js';

	function control()
	{
		parent::control();
		CIA::header('Content-Type: text/javascript; charset=UTF-8');
	}
}
