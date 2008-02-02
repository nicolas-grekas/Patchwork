<?php

class extends agent_pStudioWidget_reader
{
	function control()
	{
		$this->get->__0__ = 'ser/' . $this->get->__0__;

		parent::control();
	}

	function compose($o)
	{
		$o->data = @file_get_contents($this->realpath);

		if (isset($o->data) && false !== $o->data)
		{
			$a = @unserialize($o->data);
			if (false !== $a || $o->data === serialize(false))
			{
				$o->data = '<?php serialize(' . var_export($a, true) . ')';
			}
		}

		return $o;
	}
}
