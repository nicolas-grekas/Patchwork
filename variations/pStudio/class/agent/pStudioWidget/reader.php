<?php

class extends agent_pStudio
{
	protected $extension = '';

	function control()
	{
		$a = get_class($this);

		if (0 === strpos($a, __CLASS__ . '_'))
		{
			$a = substr($a, strlen(__CLASS__) + 1);
			$a = strtr($a, '_', '/');
			$this->get->__0__ = $a . '/' . $this->get->__0__;

			$a = explode('/', $a);
			$a = array_reverse($a);
			$this->extension = implode('.', $a);
		}

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
