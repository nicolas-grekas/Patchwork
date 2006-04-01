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


	function __construct()
	{
		parent::__construct();

		$this->_build_params['html_charset'] = 'UTF-8';
		$this->_build_params['text_charset'] = 'UTF-8';
		$this->_build_params['head_charset'] = 'UTF-8';
	}

	function doSend($build_params = null)
	{
		$body =& $this->get($build_params);
		$headers =& $this->headers();

		$to = DEBUG ? 'webmaster' : $headers['To'];
		unset($headers['To']);

		$mail = Mail::factory('mail', isset($headers['Return-Path']) ? '-f ' . escapeshellarg($headers['Return-Path']) : '' );
		$mail->send($to, $headers, $body);
	}
}
