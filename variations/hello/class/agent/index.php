<?php

class agent_index extends agent
{
	public $argv = array();

	protected $maxage = 0;

	function compose()
	{
		$a = (object) array();

		$a->hello = 'Hello World !';

		return $a;
	}
}
