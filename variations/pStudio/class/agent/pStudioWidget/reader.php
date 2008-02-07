<?php

class extends agent_pStudio
{
	public $get = array(
		'path:c',
		'low:i' => false,
		'high:i' => PATCHWORK_PATH_LEVEL,
		'$serverside:b',
	);

	protected $extension = '';

	function control()
	{
		$a = get_class($this);

		if (0 === strpos($a, __CLASS__ . '_'))
		{
			$a = substr($a, strlen(__CLASS__) + 1);
			$a = strtr($a, '_', '/') . '/';
			$this->extension = $a;
		}

		$this->get->__0__ = $this->get->path;

		parent::control();
	}

	function compose($o)
	{
		if ($a = @file_get_contents($this->realpath))
		{
			if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $a))
			{
				$o->is_binary = true;
			}
			else
			{
				$a && false !== strpos($a, "\r") && $a = strtr(str_replace("\r\n", "\n", $a), "\r", "\n");
				u::isUTF8($a) || $a = utf8_encode($a);
				$o->text = $a;
			}
		}

		return $o;
	}
}
