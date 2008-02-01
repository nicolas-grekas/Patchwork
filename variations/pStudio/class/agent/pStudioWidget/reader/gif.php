<?php

class extends agent_pStudioWidget_reader
{
	const contentType = 'image/gif';

	public $get = array(
		'__0__:c',
		'low:i',
		'high:i',
		'raw:b',
	);

	protected

	$template = 'pStudioWidget/reader/gif',
	$extension;

	function control()
	{
		if (!isset($this->extension))
		{
			$this->extension = get_class($this);
			$this->extension = strrchr($this->extension, '_');
			$this->extension = substr($this->extension, 1);
		}

		$this->get->__0__ = $this->extension . '/' . $this->get->__0__;

		parent::control();
	}

	function compose($o)
	{
		if ($this->get->raw)
		{
			header('Content-Type: ' . $this->contentType);
			p::readfile($this->dirname . $this->path, false);
		}
		else $o->extension = $this->extension;

		return $o;
	}
}
