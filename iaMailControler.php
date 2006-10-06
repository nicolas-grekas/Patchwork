#!php -q
<?php

function get_notify_url($event, $message_id)
{
	// return array($notify_url, $send_email);
}



require_once 'HTTP/Request.php';

$event = @$_SERVER['argv'][1];

if ('reply' != $event && 'bounce' != $event) die("No event or bad event specified in the first parameter. Needed value is \"reply\" or \"bounce\".\n");

$body = '';
$header = '';

while (!feof(STDIN))
{
	$header .= str_replace(array("\r\n", "\r"), array("\n", "\n"), fread(STDIN, 8192));

	$a = strpos($header, "\n\n");
	if (false !== $a)
	{
		$body = substr($header, $a);
		$header = substr($header, 0, $a) . "\nX-iaMailEnd: ";
		break;
	}
}

$send_email = false;
$notify_urls = array();

if (preg_match_all("'\n(references|to|in-reply-to):(.*?)\n[-a-z]+:'si", $header, $m)) foreach ($m as &$m)
{
	$a = strtolower($m[1]);

	if (preg_match_all("'iaM[a-f0-9]{32,}'", $m[2], $m)) foreach ($m as &$m)
	{
		if (strlen($m[0]) != 32) continue;

		$message_id = $m[0];

		if (isset($notify_urls[$message_id]))
		{
			if (!$notify_urls[$message_id]) continue;
		}
		else
		{
			$m = get_notify_url($event, $message_id);

			if (!$m)
			{
				$notify_urls[$message_id] = false;
				continue;
			}

			$send_email = $send_email || $m[1];

			$notify_urls[$message_id] = array(
				'notify_url' => $m[0],
				'send_email' => $m[1],
				'in-reply-to' => 0,
				'references' => 0
			);
		}

		switch ($a)
		{
			case 'to':
			case 'in-reply-to': $notify_urls[$message_id]['inside-to'] = 1; break;
			case 'references' : $notify_urls[$message_id]['inside-references'] = 1; break;
		}
	}
}

if ($send_email)
{
	$send_mail =& $header;
	$send_mail .= $body;

	while (!feof(STDIN)) $send_mail .= str_replace(array("\r\n", "\r"), array("\n", "\n"), fread(STDIN, 8192));
}

foreach ($notify_urls as &$a)
{
	$r = new HTTP_Request( $a['notify_url'] );
	$r->setMethod(HTTP_REQUEST_METHOD_POST);
	$r->addPostData('event', $event);
	$r->addPostData('inside-to', $a['inside-to']);
	$r->addPostData('inside-references', $a['inside-references']);
	$r->addPostData('email-body', $send_email);
	$r->sendRequest();
}
