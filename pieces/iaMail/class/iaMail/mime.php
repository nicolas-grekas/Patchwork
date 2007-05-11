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


class extends Mail_mime
{
	protected $options;

	static function send($headers, $body, $options = null)
	{
		$mail = new iaMail_mime($options);

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
		parent::__construct(CIA_WINDOWS ? "\r\n" : "\n");

		$this->options = $options;

		$this->_build_params['text_encoding'] = 'quoted-printable';
		$this->_build_params['html_charset'] = 'UTF-8';
		$this->_build_params['text_charset'] = 'UTF-8';
		$this->_build_params['head_charset'] = 'UTF-8';
	}

	protected function doSend()
	{
		$message_id = 'iaM' . CIA::uniqid();

		$this->_headers['Message-Id'] = '<' . $message_id . '@' . (isset($_SERVER['HTTP_HOST']) ? urlencode($_SERVER['HTTP_HOST']) : 'iaMail') . '>';

		$this->setObserver('reply', 'Reply-To', $message_id);
		$this->setObserver('bounce', 'Return-Path', $message_id);

		$body =& $this->get($this->options);
		$headers =& $this->headers();

		if (isset($headers['From']))
		{
			ini_set('sendmail_from', $headers['From']);

			if (!isset($headers['Reply-To'])   ) $headers['Reply-To'] = $headers['From'];
			if (!isset($headers['Return-Path'])) $headers['Return-Path'] = $headers['From'];
		}

		$to = $headers['To'];
		unset($headers['To']);

		$options = null;
		$backend = $GLOBALS['CONFIG']['email_backend'];

		switch ($backend)
		{
		case 'mail':
			$options = isset($GLOBALS['CONFIG']['email_options']) ? $GLOBALS['CONFIG']['email_options'] : '';
			if (isset($headers['Return-Path'])) $options .= ' -f ' . escapeshellarg($headers['Return-Path']);
			break;

		case 'smtp':
			$options = isset($GLOBALS['CONFIG']['email_options']) ? $GLOBALS['CONFIG']['email_options'] : array();
			break;
		}

		$mail = Mail::factory($backend, $options);
		$mail->send($to, $headers, $body);
	}

	protected function setObserver($event, $header, $message_id)
	{
		if (!isset($this->options['on' . $event])) return;

		if (isset($this->options[$event . '_email'])) $email = $this->options[$event . '_email'];
		else if (isset($GLOBALS['CONFIG'][$event . '_email'])) $email = $GLOBALS['CONFIG'][$event . '_email'];

		if (isset($this->options[$event . '_url'])) $url = $this->options['reply_url'];
		else if (isset($GLOBALS['CONFIG'][$event . '_url'])) $url = $GLOBALS['CONFIG'][$event . '_url'];

		if (!isset($email)) W("{$event}_email has not been configured.");
		else if (!isset($url)) W("{$event}_url has not been configured.");
		else
		{
			$email = sprintf($email, $message_id);

			if (isset($this->headers[$header])) $this->headers[$header] .= ', ' . $email;
			else $this->headers[$header] = $email;

			if (ini_get('allow_url_fopen'))
			{
				$context = stream_context_create(array('http' => array(
					'method' => 'POST',
					'content' => http_build_query(array(
						'message_id' => $message_id,
						"{$event}_on{$event}" => CIA::base($this->options['on' . $event], true)
					))
				)));

				file_get_contents(CIA::base($url, true), false, $context);
			}
			else
			{
				$r = new HTTP_Request( CIA::base($url, true) );
				$r->setMethod(HTTP_REQUEST_METHOD_POST);
				$r->addPostData('message_id', $message_id);
				$r->addPostData("{$event}_on{$event}", CIA::base($this->options['on' . $event], true));
				$r->sendRequest();
			}
		}
	}
}
