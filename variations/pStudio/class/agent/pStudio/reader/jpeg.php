<?php

class extends agent_pStudio_reader_gif
{
	protected $rawContentType = 'image/jpeg';

	function compose($o)
	{
		$o = parent::compose($o);

		if (extension_loaded('exif'))
		{
			$exif = exif_read_data($this->realpath);

			E($exif);
		}

		return $o;
	}
}
