<?php

include('Mail.php');
include('Mail/mime.php');

// $error_reporting = error_reporting(0);
// error_reporting($error_reporting);

class iaMail
{
	protected $mail;
	protected $headers;
	protected $text_body;
	protected $html_body;

	protected $head_charset = 'ISO-8859-1';
	protected $text_charset = 'ISO-8859-1';
	protected $html_charset = 'ISO-8859-1';


	static function send($headers, $body)
	{
		$mail = new iaMail;

		$mail->setHead($headers);
		$mail->setText($body);

		$mail->doSend();
	}

	static function sendAgent($headers, $agent, $argv = array())
	{
		$mail = new iaMail_agent($agent, $argv);
		$mail->setHead($headers);
		$mail->doSend();
	}


	function __construct()
	{
		$this->mail = new Mail_mime;
	}

	function setHead($a) {$this->headers = $a;}
	function setText($a) {$this->text_body = $a;}
	function setHtml($a) {$this->html_body = $a;}

	function addAttachment($file, $c_type = 'application/octet-stream', $dataname = '')
	{
		$this->mail->addAttachment($file, $c_type, $dataname, '' !== $dataname);
	}

	function setCharset($charset, $section = '')
	{
		switch ($section)
		{
			case 'head': $this->head_charset = $charset; break;
			case 'text': $this->text_charset = $charset; break;
			case 'html': $this->html_charset = $charset; break;

			case '': 
				$this->head_charset = $charset;
				$this->text_charset = $charset;
				$this->html_charset = $charset;

			default: E('Unknown $section : ' . $section);
		}
	}

	function doSend()
	{
		if (isset($this->text_body)) $this->mail->setTxtBody( mb_convert_encoding($this->text_body, $this->text_charset) );
		if (isset($this->html_body)) $this->mail->setHtmlBody( mb_convert_encoding($this->html_body, $this->html_charset) );

		$headers = array();
		foreach ($this->headers as $k => $v) $headers[$k] = mb_convert_encoding($v, $this->head_charset);

		$to = DEBUG ? 'webmaster' : $headers['To'];
		unset($headers['To']);

		$body = $this->mail->get(array(
			'head_charset' => $this->head_charset,
			'text_charset' => $this->text_charset,
			'html_charset' => $this->html_charset,
		));
		$headers = $this->mail->headers($headers);

		$mail = Mail::factory('mail', isset($headers['Return-Path']) ? '-f ' . escapeshellarg($headers['Return-Path']) : '' );
		$mail->send($to, $headers, $body);

	}
}
