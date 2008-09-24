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


class extends Mail_mime
{
	protected $options;

	static function send($headers, $body, $options = null)
	{
		$mail = new pMail_mime($options);

		$mail->headers($headers);
		$mail->setTxtBody($body);

		$mail->doSend();
	}

	static function sendAgent($headers, $agent, $args = array(), $options = null)
	{
		$mail = new pMail_agent($agent, $args, $options);
		$mail->headers($headers);
		$mail->doSend();
	}


	function __construct($options = null)
	{
		$eol = "\r\n";

		if ('smtp' !== $CONFIG['pMail.backend'])
		{
			false === strpos(PHP_OS, 'WIN') && $eol = "\n";
			defined('PHP_EOL') && $eol = PHP_EOL;
		}

		parent::__construct($eol);

		$this->options = $options;

		$this->_build_params['head_charset' ] = 'utf-8';

		$this->_build_params['text_charset' ] = 'utf-8';
		$this->_build_params['text_encoding'] = 'base64';

		$this->_build_params['html_charset' ] = 'utf-8';
		$this->_build_params['html_encoding'] = 'base64';

		if (!empty($options['attachments']) && is_array($options['attachments']))
		{
			$tmpToken = isset($options['attachments.tmpToken']) ? '~' . $options['attachments.tmpToken'] : false;

			foreach ($options['attachments'] as $name => $file)
			{
				if (!file_exists($file))
				{
					W(__CLASS__ . ': file attachment not found (' . $file . ')');
					continue;
				}

				is_int($name) && $name = '';

				$c_type = strtolower(strrchr($name ? $name : $file, '.'));
				$c_type = isset(patchwork_static::$contentType[$c_type])
					? patchwork_static::$contentType[$c_type]
					: 'application/octet-stream';

				$this->addAttachment($file, $c_type, $name);

				$tmpToken
					&& $tmpToken === substr($file, -strlen($tmpToken))
					&& register_shutdown_function(array(__CLASS__, 'unlink'), $file);
			}
		}
	}

	protected function doSend()
	{
		$message_id = 'pM' . p::uniqid();

		$this->_headers['Message-Id'] = '<' . $message_id . '@' . $_SERVER['HTTP_HOST']. '>';

		$body =& $this->get();
		$headers =& $this->headers();

		if (!isset($headers['From']) && $CONFIG['pMail.from']) $headers['From'] = $CONFIG['pMail.from'];
		if ( isset($headers['From']) && !$headers['From']) W("Email is likely not to be sent: From header is empty.");

		if (isset($headers['Return-Path'])) $headers['Errors-To'] =& $headers['Return-Path'];
		else if (isset($headers['Errors-To'])) $headers['Return-Path'] =& $headers['Errors-To'];
		else if (isset($headers['From']))
		{
			$headers['Return-Path'] = $headers['From'];
			$headers['Errors-To'] =& $headers['Return-Path'];
		}

		$this->setObserver('reply', 'Reply-To', $message_id);
		$this->setObserver('bounce', 'Return-Path', $message_id);

		isset($headers['From']) && ini_set('sendmail_from', $headers['From']);

		$to = $headers['To'];
		unset($headers['To']);

		isset($headers['Return-Path'])
			&& preg_match("'" . FILTER::EMAIL_RX . "'", $headers['Return-Path'], $options)
			&& $headers['Return-Path'] = $options[0];

		$options = null;
		$backend = $CONFIG['pMail.backend'];

		switch ($backend)
		{
		case 'mail':
			$options = $CONFIG['pMail.options'];
			if (isset($headers['Return-Path'])) $options .= ' -f ' . escapeshellarg($headers['Return-Path']);
			break;

		case 'smtp':
			$options = $CONFIG['pMail.options'];
			break;
		}

		$mail = Mail::factory($backend, $options);
		$mail->send($to, $headers, $body);
	}

	protected function setObserver($event, $header, $message_id)
	{
		if (!isset($this->options['on' . $event])) return;

		if (isset($this->options[$event . '_email'])) $email = $this->options[$event . '_email'];
		else if (isset($CONFIG[$event . '_email'])) $email = $CONFIG[$event . '_email'];

		if (isset($this->options[$event . '_url'])) $url = $this->options['reply_url'];
		else if (isset($CONFIG[$event . '_url'])) $url = $CONFIG[$event . '_url'];

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
						"{$event}_on{$event}" => p::base($this->options['on' . $event], true)
					))
				)));

				file_get_contents(p::base($url, true), false, $context);
			}
			else
			{
				$r = new HTTP_Request( p::base($url, true) );
				$r->setMethod(HTTP_REQUEST_METHOD_POST);
				$r->addPostData('message_id', $message_id);
				$r->addPostData("{$event}_on{$event}", p::base($this->options['on' . $event], true));
				$r->sendRequest();
			}
		}
	}

	static function unlink($file)
	{
		@unlink($file);
	}
}
