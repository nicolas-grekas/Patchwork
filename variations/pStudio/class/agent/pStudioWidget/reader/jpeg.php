<?php

class extends agent_pStudioWidget_reader_gif
{
	const contentType = 'image/jpeg';

	function compose($o)
	{
		if (isset($this->extension) && extension_loaded('exif'))
		{
			$exif = exif_read_data($this->realpath);

			E($exif);
		}

		return $o;
	}
}
