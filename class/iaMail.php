<?php

include('Mail.php');
include('Mail/mime.php');

// $error_reporting = error_reporting(0);
// error_reporting($error_reporting);

class iaMail extends Mail_mime
{
	protected $convert_text_encoding = false;
	protected $convert_html_encoding = false;

	static function send($headers, $body)
	{
		$mail = new iaMail;

		$mail->headers($headers);
		$mail->setTxtBody($body);

		$mail->doSend();
	}

	static function sendAgent($headers, $agent, $argv = array(), $lang = 'default')
	{
		$mail = new iaMail_agent($agent, $argv, $lang);
		$mail->headers($headers);
		$mail->doSend();
	}


	function setTXTBody($data, $isfile = false, $append = false)
	{
		$this->convert_text_encoding = true;
		parent::setTXTBody($data, $isfile, $append);
	}

	function setHTMLBody($data, $isfile = false)
	{
		$this->convert_html_encoding = true;
		parent::setHTMLBody($data, $isfile);
	}

	function doSend($build_params = null)
	{
		if ($this->convert_text_encoding) $this->_txtbody = mb_convert_encoding($this->_txtbody, $this->_build_params['text_charset']);
		if ($this->convert_html_encoding) $this->_htmlbody = mb_convert_encoding($this->_htmlbody, $this->_build_params['html_charset']);

		$headers = array();
		foreach ($this->_headers as $k => $v) $headers[$k] = mb_convert_encoding($v, $this->_build_params['head_charset']);

		$to = DEBUG ? 'webmaster' : $headers['To'];
		unset($headers['To']);

		$body =& $this->get($build_params);
		$headers =& $this->headers($headers);

		$mail = Mail::factory('mail', isset($headers['Return-Path']) ? '-f ' . escapeshellarg($headers['Return-Path']) : '' );
		$mail->send($to, $headers, $body);
	}
}
