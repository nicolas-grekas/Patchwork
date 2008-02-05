<?php

class extends agent_pStudioWidget_reader_php
{
	const contentType = 'image/gif';

	protected $template = 'pStudioWidget/reader/gif';

	function compose($o)
	{
		if ($this->get->{'$serverside'})
		{
			header('Content-Type: ' . $this->contentType);
			p::readfile($this->realpath, false);
		}
		else
		{
			if (extension_loaded('exif') && in_array(get_class($this),array('jpeg','jpg')))
			{
				$metainfos = exif_read_data($this->realpath);

				$o->filesize = $metainfos['FileSize'];
				$o->width = $metainfos['ExifImageWidth'];
				$o->height = $metainfos['ExifImageLength'];
			}
			else
			{
				list($o->width, $o->height) = getimagesize($this->realpath);

				$o->filesize  = filesize($this->realpath);
			}

			$o->extension = $this->extension;
		}

		return $o;
	}
}
