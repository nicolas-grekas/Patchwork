<?php

require_once 'HTTP/Request.php';

class iaMail_agent extends iaMail
{
	protected $agent;
	protected $argv;
	protected $lang;

	static protected $imageCache = array();

	function __construct($agent, $argv = array(), $lang = 'default')
	{
		$this->agent = $agent;
		$this->argv = $argv;
		$this->lang = 'default' == $lang ? CIA::__LANG__() : $lang;

		parent::__construct();
	}

	function doSend()
	{
		$lang = CIA::__LANG__($this->lang);

		ob_start();
		IA_php::loadAgent($this->agent, $this->argv);
		$html = ob_get_contents();

		CIA::__LANG__($lang);

		$html = preg_replace_callback('/(\s)(src|background)\s*=\s*(["\'])?((?(3)[^(?:\3)]*|[^\s>]*)\.(jpe?g|png|gif))(?(3)\3)/iu', array($this, 'addRawImage'), $html);

		$this->setHTMLBody($html);
		$this->setTXTBody( CONVERT::data($html, 'html', 'txt') );

		parent::doSend();
	}

	protected function addRawImage($match)
	{
		$url = $match[4];

		if (!preg_match("'^(ftp|https?)://'iu", $url)) 
		{
			if ('/' != substr($url, 0, 1)) $url = CIA::__ROOT__() . $url;

			$url = CIA::__HOST__() . $url;
		}

		if (isset(self::$imageCache[$url])) $data =& self::$imageCache[$url];
		else
		{
			$data = new HTTP_Request($url);
			$data->sendRequest();
			$data = $data->getResponseBody();
			self::$imageCache[$url] =& $data;
		}

		switch (strtolower($match[5]))
		{
			case 'png': $mime = 'image/png'; break;
			case 'gif': $mime = 'image/gif'; break;
			default: $mime = 'image/jpeg';
		}

		$this->addHtmlImage($data, $mime, $match[4], false);

		$a =& $this->_html_images[ count($this->_html_images) - 1 ];

		return $match[1] . $match[2] . '=cid:' . $a['cid'];
	}
}
