<?php

class agent_index extends agent
{
	function compose($o)
	{
		$o->hello = 'Hello World !';

		return $o;
	}
}
