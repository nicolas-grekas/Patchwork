<?php

class extends agent_pStudioWidget_reader_html
{
	function control()
	{
		$a = explode('/', $this->get->__0__, 2);

		switch ($a[0])
		{
			case 'js' : $this->language = 'javascript'; break;
			case 'css': $this->language = 'css';        break;
		}

		parent::control();
	}
}

