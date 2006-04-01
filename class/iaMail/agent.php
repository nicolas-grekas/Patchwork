<?php

class iaMail_agent extends iaMail
{
	protected $agent;
	protected $argv;
	protected $lang;

	static protected $imageCache = array();

	function __construct($agent, $argv = array(), $lang = 'CIA_LANG')
	{
		$this->agent = $agent;
		$this->argv = $argv;
		$this->lang = 'CIA_LANG' == $lang ? CIA_LANG : $lang;

		parent::construct();
	}

	function doSend()
	{
		ob_start();
		IA_php::loadAgent($this->agent, $this->argv);
		$html = ob_get_contents();

		$html = preg_replace_callback('/(\s)(src|background)\s*=\s*(["\'])?((?(3)[^\3]*|[^\s>]*)\.(jpe?g|png|gif))(?(3)\3)/iu', array($this, 'addImage'), $html);
		// TODO? manage images in CSS. See http://www.w3.org/TR/REC-CSS2/syndata.html#uri

		$this->html_body = $html;
		$this->text_body = CONVERT::data($html, 'html', 'txt');

		parent::doSend();
	}

	protected function addImage($match)
	{
		$url = $match[4];

		if (isset(self::$imageCache[$url])) $data =& self::$imageCache[$url];
		else
		{
			if (!preg_match("'^(ftp|https?)://'iu", $url)) 
			{
				if (!isset($_SERVER['HTTP_HOST'])) return $match[0]; // XXX: because of this, the mailer doesn't work in CLI mode

				if ('/' != substr($url, 0, 1)) $url = CIA_ROOT . $url;

				$url = 'http' . (@$_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $url;
			}

			$data = file_get_contents($url);
			self::$imageCache[ $match[4] ] =& $data;
		}

		$name = CIA::uniqid();

		switch (strtolower($match[5]))
		{
			case 'png': $mime = 'image/png'; break;
			case 'gif': $mime = 'image/gif'; break;
			default: $mime = 'image/jpeg';
		}

		$this->mail->addHtmlImage($data, $mime, $name, false);

		return $match[1] . $match[2] . '=' . $name;
	}
}
