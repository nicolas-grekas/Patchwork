<?php

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

		$html = preg_replace_callback('/(\s)(src|background)\s*(=)\s*(["\'])?((?(4)[^(?:\4)]*|[^\s>]*)\.(jpe?g|png|gif))(?(4)\4)/iu', array($this, 'addRawImage'), $html);

		// Limited support for http://www.w3.org/TR/REC-CSS2/syndata.html#uri
		$html = preg_replace_callback("/([\s:])(url)(\()\s*([\"'])?([^\n\r]*\.(jpe?g|png|gif))(?(4)\4)\s*(\))/iu", array($this, 'addRawImage'), $html);

		$this->setHTMLBody($html);
		$this->setTXTBody( CONVERT::data($html, 'html', 'txt') );

		parent::doSend();
	}

	protected function addRawImage($match)
	{
		$url = $match[5];

		if (isset(self::$imageCache[$url])) $data =& self::$imageCache[$url];
		else
		{
			if (!preg_match("'^(ftp|https?)://'iu", $url)) 
			{
				if ('/' != substr($url, 0, 1)) $url = CIA::__ROOT__() . $url;

				$url = CIA::__HOST__() . $url;
			}

			$data = file_get_contents($url);
			self::$imageCache[ $match[5] ] =& $data;
		}

		switch (strtolower($match[6]))
		{
			case 'png': $mime = 'image/png'; break;
			case 'gif': $mime = 'image/gif'; break;
			default: $mime = 'image/jpeg';
		}

		$this->addHtmlImage($data, $mime, $match[5], false);

		$a =& $this->_html_images[ count($this->_html_images) - 1 ];

		return $match[1] . $match[2] . $match[3] . 'cid:' . $a['cid'] . @$match[7];
	}
}
