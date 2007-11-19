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


class extends pMail_mime
{
	protected

	$agent,
	$args,
	$lang;


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
			$this->headers(array('Subject' => trim(html_entity_decode($title[1], ENT_QUOTES, 'UTF-8'))));
		}


		// HTML cleanup

		$html = preg_replace('#<(head|script|title|applet|frameset|i?frame)\b[^>]*>.*?</\1\b[^>]*>#is', '', $html);
		$html = preg_replace('#</?(?:!DOCTYPE|html|meta|body|base|link)\b[^>]*>#is', '', $html);
		$html = preg_replace('#<!--.*?-->#s', '', $html);
		$html = trim($html);

		$html = preg_replace_callback(
			'/(\s)(src|background|href)\s*=\s*(["\'])?((?(3)(?:[^\3]*)|[^\s>]*))(?(3)\3)/iu',
			array($this, 'cleanUrlAttribute'),
			$html
		);

		if (isset($this->options['embedImages']) && $this->options['embedImages'])
		{
			$html = preg_replace_callback(
				'/(\s)(src|background)="([^"]+\.(jpe?g|png|gif))"/iu',
				array($this, 'addRawImage'),
				$html
			);
		}

		$this->setHTMLBody($html);


		// Prepare HTML for text convertion

		$html = preg_replace_callback(
			'#<a\b[^>]*\shref="([^"]*)"[^>]*>(.*?)</a\b[^>]*>#isu',
			array($this, 'buildTextAnchor'),
			$html
		);

		$html = preg_replace('#<(?:b|strong)\b[^>]*>(\s*)#isu' , '$1*', $html);
		$html = preg_replace('#(\s*)</(?:b|strong)\b[^>]*>#isu', '*$1', $html);
		$html = preg_replace('#<(?:i|em)\b[^>]*>(\s*)#isu' , '$1/', $html);
		$html = preg_replace('#(\s*)</(?:i|em)\b[^>]*>#isu', '/$1', $html);
		$html = preg_replace('#<u\b[^>]*>(\s*)#isu' , '$1_', $html);
		$html = preg_replace('#(\s*)</u\b[^>]*>#isu', '_$1', $html);

		$c = new convert_txt_html(65);
		$this->setTXTBody( $c->convertData($html) );

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
		$a = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
		$m = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));

		if (false === stripos(urldecode($a), urldecode($m)))
		{
			$m = preg_replace_callback('"[^-a-z0-9_.!~*\'(),/?:@&=+$#]+"i', array($this, 'rawurlencodeCallback'), $m);
			$a .= " <{$m}> ";
		}

		return htmlspecialchars($a);
	}

	protected function rawurlencodeCallback($m)
	{
		return rawurlencode($m[0]);
	}
}
