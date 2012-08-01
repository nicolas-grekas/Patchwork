#!php -q
<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

function get_notify_url($event, $message_id)
{
    // return array($notify_url, $send_email);
}


if (!isset($_SERVER['argv'][1])) die("No event specified as first parameter. Needed value is \"reply\" or \"bounce\".\n");

$event = $_SERVER['argv'][1];

if ('reply' != $event && 'bounce' != $event) die("Bad event specified as first parameter. Needed value is \"reply\" or \"bounce\".\n");

$body = '';
$header = '';

while (!feof(STDIN))
{
    $a = fread(STDIN, 8192);
    if (false !== strpos($a, "\r")) $a = strtr(str_replace("\r\n", "\n", $a), "\r", "\n");
    $header .= $a;

    $a = strpos($header, "\n\n");
    if (false !== $a)
    {
        $body = substr($header, $a);
        $header = substr($header, 0, $a) . "\nX-pMailEnd: ";
        break;
    }
}

$send_email = false;
$notify_urls = array();

if (preg_match_all("'\n(references|to|in-reply-to):(.*?)\n[-a-z]+:'si", $header, $m)) foreach ($m as &$m)
{
    $a = strtolower($m[1]);

    if (preg_match_all("'pM[-_0-9a-zA-Z]{32,}'", $m[2], $m)) foreach ($m as &$m)
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

    while (!feof(STDIN))
    {
        $a = fread(STDIN, 8192);
        if (false !== strpos($a, "\r")) $a = strtr(str_replace("\r\n", "\n", $a), "\r", "\n");
        $send_mail .= $a;
    }
}

if (ini_get_bool('allow_url_fopen'))
{
    foreach ($notify_urls as &$a)
    {
        $context = stream_context_create(array('http' => array(
            'method' => 'POST',
            'content' => http_build_query(array(
                'event' => $event,
                'inside-to' => $a['inside-to'],
                'inside-references' => $a['inside-references'],
                'email-body' => $send_email,
            ))
        )));

        file_get_contents($a['notify_url'], false, $context);
    }
}
else
{
    require_once 'HTTP/Request.php';

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
}
