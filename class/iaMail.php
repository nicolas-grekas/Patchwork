<?php

require_once 'Mail.php';
require_once 'Mail/mime.php';

// $error_reporting = error_reporting(0);
// error_reporting($error_reporting);

class iaMail extends Mail_mime
{
	protected $reply_email;
	protected $reply_url;

	protected $bounce_email;
	protected $bounce_url;

	protected $options;

	static function send($headers, $body, $options = null)
	{
		$mail = new iaMail($options);

		$mail->headers($headers);
		$mail->setTxtBody($body);

		$mail->doSend();
	}

	static function sendAgent($headers, $agent, $argv = array(), $options = null)
	{
		$mail = new iaMail_agent($agent, $argv, $options);
		$mail->headers($headers);
		$mail->doSend();
	}


	function __construct($options = null)
	{
		parent::__construct();


		if (isset($options['reply_email'])) $this->reply_email = $options['reply_email'];
		else if (isset($GLOBALS['CONFIG']['reply_email'])) $this->reply_email = $GLOBALS['CONFIG']['reply_email'];

		if (isset($options['reply_url'])) $this->reply_url = $options['reply_url'];
		else if (isset($GLOBALS['CONFIG']['reply_url'])) $this->reply_url = $GLOBALS['CONFIG']['reply_url'];


		if (isset($options['bounce_email'])) $this->bounce_email = $options['bounce_email'];
		else if (isset($GLOBALS['CONFIG']['bounce_email'])) $this->bounce_email = $GLOBALS['CONFIG']['bounce_email'];

		if (isset($options['bounce_url'])) $this->bounce_url = $options['bounce_url'];
		else if (isset($GLOBALS['CONFIG']['bounce_url'])) $this->bounce_url = $GLOBALS['CONFIG']['bounce_url'];


		$this->options = $options;

		$this->_build_params['html_charset'] = 'UTF-8';
		$this->_build_params['text_charset'] = 'UTF-8';
		$this->_build_params['head_charset'] = 'UTF-8';
	}

	function doSend()
	{
		$headers =& $this->_headers;

		$message_id = CIA::uniqid();

		$headers['Message-Id'] = "<{$message_id}@iaMail>";

		if (isset($this->options['onreply']))
		{
			if (!isset($this->reply_email)) E('No reply_email has been configured !');
			else if (!isset($this->reply_url)) E('No reply_url has been configured !');
			else
			{
				// Add Reply-To to the mail headers
				$reply_email = sprintf($this->reply_email, 'R' . $message_id);

				if (isset($headers['Reply-To'])) $headers['Reply-To'] .= ', ' . $reply_email;
				else $headers['Reply-To'] = $reply_email;


				// Notify $this->reply_url that a new reply should be observed
				require_once 'HTTP/Request.php';

				$r = new HTTP_Request( CIA::getUri($this->reply_url) );
				$r->setMethod(HTTP_REQUEST_METHOD_POST);
				$r->addPostData('message_id', $message_id);
				$r->addPostData('reply_onreply', CIA::getUri($this->options['onreply']));
				$r->sendRequest();
			}
		}

		if (isset($this->options['onbounce']))
		{
			if (!isset($this->bounce_email)) E('No bounce_email has been configured !');
			else if (!isset($this->bounce_url)) E('No bounce_url has been configured !');
			else
			{
				// Add Return-Path to the mail headers
				$bounce_email = sprintf($this->bounce_email, 'B' . $message_id);

				if (isset($headers['Return-Path'])) $headers['Return-Path'] .= ', ' . $bounce_email;
				else $headers['Return-Path'] = $bounce_email;


				// Notify $this->bounce_url that a new bounce should be observed
				require_once 'HTTP/Request.php';

				$r = new HTTP_Request( CIA::getUri($this->bounce_url) );
				$r->setMethod(HTTP_REQUEST_METHOD_POST);
				$r->addPostData('message_id', $message_id);
				$r->addPostData('bounce_onbounce', CIA::getUri($this->options['onbounce']));
				$r->sendRequest();
			}
		}

		$body =& $this->get($this->options);
		$headers =& $this->headers();

		$to = DEBUG ? 'webmaster' : $headers['To'];
		unset($headers['To']);

		$mail = Mail::factory('mail', isset($headers['Return-Path']) ? '-f ' . escapeshellarg($headers['Return-Path']) : '' );
		$mail->send($to, $headers, $body);
	}

	// The original _encodeHeaders of Mail_mime is bugged !
	function _encodeHeaders($input)
	{
		foreach ($input as $hdr_name => $hdr_value)
		{
			if (preg_match('/[\x80-\xFF]/', $hdr_value))
			{
				$hdr_value = preg_replace('/[=_\?\x00-\x1F\x80-\xFF]/e', '"=".strtoupper(dechex(ord("\0")))', $hdr_value);
				$hdr_value = str_replace(' ', '_', $hdr_value);

				$input[$hdr_name] = '=?' . $this->_build_params['head_charset'] . '?Q?' . $hdr_value . '?=';
			}
		}

		return $input;
	}
}
