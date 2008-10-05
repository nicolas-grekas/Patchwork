<?php

class extends agent_pStudio_opener_gif
{
	protected $rawContentType = 'image/jpeg';

	protected function composeReader($o)
	{
		$o = parent::composeReader($o);

		if (extension_loaded('exif'))
		{
			$exif = exif_read_data($this->realpath);

			E($exif);
		}

		return $o;
	}
}
