<?php

class extends agent_pStudioWidget_reader_html
{
	function control()
	{
		switch (pipe_pStudio_extension::php($this->get->path))
		{
			case 'ptl/js/' : $this->language = 'javascript'; break;
			case 'ptl/css/': $this->language = 'css';        break;
		}

		parent::control();
	}
}

