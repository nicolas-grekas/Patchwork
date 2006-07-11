<?php

class extends agent
{
	function compose($o)
	{
		$o->hello = 'Hello World !';

		return $o;
	}
}
