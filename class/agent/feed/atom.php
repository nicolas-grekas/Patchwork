<?php

class extends agent_bin
{
	function control()
	{
		CIA::header('Content-Type: application/atom+xml');
	}
}
