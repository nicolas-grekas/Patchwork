<?php

class extends agent_pStudioWidget_reader_php
{
	const contentType = 'image/gif';

	public $get = array(
		'__0__:c',
		'low:i' => false,
		'high:i' => PATCHWORK_PATH_LEVEL,
		'raw:b',
	);

	protected $template = 'pStudioWidget/reader/gif';

	function compose($o)
	{
		if ($this->get->raw)
		{
			header('Content-Type: ' . $this->contentType);
			p::readfile($this->realpath, false);
		}
		else $o->extension = $this->extension;

		return $o;
	}
}
