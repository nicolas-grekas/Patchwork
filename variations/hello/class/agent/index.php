<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class extends agent
{
	function compose($o)
	{
		$o->hello = 'Hello World !';

		return $o;
	}
}
