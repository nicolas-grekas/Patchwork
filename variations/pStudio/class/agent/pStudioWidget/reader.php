<?php

class extends agent_pStudio
{
	function control()
	{
		$this->get->__0__ = pStudio::decFilename($this->get->__0__);

		parent::control();
	}

	function compose($o)
	{
		if ($a = @file_get_contents($this->realpath))
		{
			$a && false !== strpos($a, "\r") && $a = strtr(str_replace("\r\n", "\n", $a), "\r", "\n");
			u::isUTF8($a) || $a = utf8_encode($a);
		}

		$o->data = $a;

		return $o;
	}
}
