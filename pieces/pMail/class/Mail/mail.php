<?php

class Mail_mail extends self
{
	function send($recipients, $headers, $body)
	{
		$this->_sanitizeHeaders($headers);
		isset($headers['From']) && ini_set('sendmail_from', $headers['From']);
		return parent::send($recipients, $headers, $body);
	}
}
