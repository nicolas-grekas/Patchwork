<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


isset($CONFIG['email_backend']) || $CONFIG['email_backend'] = 'mail';
isset($CONFIG['email_options']) || $CONFIG['email_options'] = '';

class extends self
{
	// The original _encodeHeaders of Mail_mime is bugged !
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
		if (!$isfile) $data = str_replace("\n", $this->_eol, strtr(str_replace("\r\n", "\n", $data), "\r", "\n"));

		return parent::setTXTBody($data, $isfile, $append);
	}

	// Add line feed correction
	function setHTMLBody($data, $isfile = false)
	{
		if (!$isfile) $data = str_replace("\n", $this->_eol, strtr(str_replace("\r\n", "\n", $data), "\r", "\n"));

		return parent::setHTMLBody($data, $isfile);
	}
}
