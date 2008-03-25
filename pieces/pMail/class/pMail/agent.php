<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class extends pMail_mime
{
	protected

	$agent,
	$args,
	$lang,
	$textAnchor = array();


	static protected $imageCache = array();


	function __construct($agent, $args = array(), $options = null)
	{
		$this->agent = $agent;
		$this->args = (array) $args;
		$this->lang = isset($options['lang']) ? $options['lang'] : p::__LANG__();

		parent::__construct($options);
	}

	protected function doSend()
	{
		$html = patchwork_serverside::returnAgent($this->agent, $this->args, $this->lang);

		if (!isset($this->_headers['Subject']) && preg_match("'<title[^>]*>(.*?)</title[^>]*>'isu", $html, $title))
		{
			$this->headers(array('Subject' => trim(html_entity_decode($title[1], ENT_COMPAT, 'UTF-8'))));
		}


		// HTML cleanup

		// Remove noisy tags
		$html = preg_replace('#<(head|script|title|applet|frameset|i?frame)\b[^>]*>.*?</\1\b[^>]*>#is', '', $html);
		$html = preg_replace('#</?(?:!DOCTYPE|html|meta|body|base|link)\b[^>]*>#is', '', $html);
		$html = preg_replace('#<!--.*?-->#s', '', $html);
		$html = trim($html);

		// Clean up URLs in attributes
		$html = preg_replace_callback(
			'/(\s)(src|background|href)\s*=\s*(["\'])?((?(3).*?|[^\s>]*))(?(3)\3)/iu',
			array($this, 'cleanUrlAttribute'),
			$html
		);

		if (isset($this->options['embedImages']) && $this->options['embedImages'])
		{
			// Embed images
			$html = preg_replace_callback(
				'/(\s)(src|background)="([^"]+\.(jpe?g|png|gif))"/iu',
				array($this, 'addRawImage'),
				$html
			);
		}

		$this->setHTMLBody($html);


		// Prepare HTML for text conversion

		// Inline URLs
		$html = preg_replace_callback(
			'#<a\b[^>]*\shref="([^"]*)"[^>]*>(.*?)</a\b[^>]*>#isu',
			array($this, 'buildTextAnchor'),
			$html
		);

		// Remove <sub> and <sup> tags
		$html = preg_replace('#<(/?)su[bp]\b([^>]*)>#iu' , '<$1span$2>', $html);

		// Style according to the Netiquette
		$html = preg_replace('#<(?:b|strong)\b[^>]*>(\s*)#iu' , '$1*', $html);
		$html = preg_replace('#(\s*)</(?:b|strong)\b[^>]*>#iu', '*$1', $html);
		$html = preg_replace('#<u\b[^>]*>(\s*)#iu' , '$1_', $html);
		$html = preg_replace('#(\s*)</u\b[^>]*>#iu', '_$1', $html);

		$c = new converter_txt_html(78);
		$html = $c->convertData($html);

		$html = strtr($html, $this->textAnchor);
		$this->textAnchor = array();

		$this->setTXTBody($html);

		parent::doSend();
	}

	protected function cleanUrlAttribute($m)
	{
		return $m[1] . $m[2] . '="' . str_replace('"', '&quot;', p::base($m[4], true)) . '"';
	}

	protected function addRawImage($m)
	{
		$url = $m[3];

		if (isset(self::$imageCache[$url])) $data =& self::$imageCache[$url];
		else
		{
			if (ini_get('allow_url_fopen')) $data = file_get_contents($url);
			else
			{
				$data = new HTTP_Request($url);
				$data->sendRequest();
				$data = $data->getResponseBody();
			}

			self::$imageCache[$url] =& $data;
		}

		switch (strtolower($m[4]))
		{
			case 'png': $mime = 'image/png'; break;
			case 'gif': $mime = 'image/gif'; break;
			default: $mime = 'image/jpeg';
		}

		$this->addHtmlImage($data, $mime, $url, false);

		$a =& $this->_html_images[ count($this->_html_images) - 1 ];

		return $m[1] . $m[2] . '="cid:' . $a['cid'] . '"';
	}

	protected function buildTextAnchor($m)
	{
		$a = $m[2];
		$m = trim($m[1]);
		$m = preg_replace('"^mailto:\s*"i', '', $m);

		$b = false !== strpos($m, '&') ? html_entity_decode($m, ENT_COMPAT, 'UTF-8') : $m;
		$b = preg_replace_callback('"[^-a-zA-Z0-9_.~,/?:@&=+$#%]+"', array($this, 'rawurlencodeCallback'), $b);
		$len = strlen($b);

		$c = '';
		do $c .= md5(mt_rand());
		while (strlen($c) < $len);
		$c = substr($c, 0, $len);

		$this->textAnchor[$c] = $b;

		if (false === stripos($a, $m)) $a .= " &lt;{$c}&gt;";
		else $a = str_ireplace($m, $c, $a);

		return $a;
	}

	protected function rawurlencodeCallback($m)
	{
		return rawurlencode($m[0]);
	}
}
