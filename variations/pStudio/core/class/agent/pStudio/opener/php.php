<?php

class extends agent_pStudio_opener
{
	protected $language;

	function compose($o)
	{
		isset($this->language) || $this->language = substr($this->extension, 0, -1);
		$o = parent::compose($o);
		$o->language = $this->language;
		return $o;
	}

	protected function composeReader($o)
	{
		$a = @file_get_contents($this->realpath);

		if (false !== $a)
		{
			$a && false !== strpos($a, "\r") && $a = strtr(str_replace("\r\n", "\n", $a), "\r", "\n");
			u::isUTF8($a) || $a = utf8_encode_1252($a);

			$o->code = pStudio_highlighter::highlight($a, $this->language, true);
		}

		return $o;
	}
}
