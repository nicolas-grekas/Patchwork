<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends self
{
	// The original Mail_mime->_encodeHeaders() is bugged !

	function _encodeHeaders($input)
	{
		$ns = "[^\(\)<>@,;:\"\/\[\]\r\n]*";

		foreach ($input as &$hdr_value)
		{
			$hdr_value = preg_replace_callback(
				"/{$ns}(?:[\\x80-\\xFF]{$ns})+/",
				array($this, '_encodeHeaderWord'),
				$hdr_value
			);
		}

		return $input;
	}

	protected function _encodeHeaderWord($word)
	{
		$word = preg_replace('/[=_\?\x00-\x1F\x80-\xFF]/e', '"=".strtoupper(dechex(ord("\0")))', $word[0]);

		preg_match('/^( *)(.*?)( *)$/', $word, $w);

		$word =& $w[2];
		$word = str_replace(' ', '_', $word);

		$start = '=?' . $this->_build_params['head_charset'] . '?Q?';
		$offsetLen = strlen($start) + 2;

		$w[1] .= $start;

		while ($offsetLen + strlen($word) > 75)
		{
			$splitPos = 75 - $offsetLen;

			switch ('=')
			{
				case substr($word, $splitPos - 2, 1): --$splitPos;
				case substr($word, $splitPos - 1, 1): --$splitPos;
			}

			$w[1] .= substr($word, 0, $splitPos) . "?={$this->_eol} {$start}";
			$word = substr($word, $splitPos);
		}

		return $w[1] . $word . '?=' . $w[3];
	}


	// Add line feeds correction

	function setTXTBody($data, $isfile = false, $append = false)
	{
		$isfile || $this->_fixEOL($data);
		return parent::setTXTBody($data, $isfile, $append);
	}

	function setHTMLBody($data, $isfile = false)
	{
		$isfile || $this->_fixEOL($data);
		return parent::setHTMLBody($data, $isfile);
	}

	function &_file2str($file_name)
	{
		$file_name =& parent::_file2str($file_name);
		$this->fixEOL($file_name);
		return $file_name;
	}

	protected function _fixEOL(&$a)
	{
		false !== strpos($a, "\r") && $a = strtr(str_replace("\r\n", "\n", $a), "\r", "\n");
		"\n"  !== $this->_eol      && $a = str_replace("\n", $this->_eol, $a);
	}
}
