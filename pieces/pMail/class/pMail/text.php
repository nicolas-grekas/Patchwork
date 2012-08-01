<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork as p;

class pMail_text extends Mail_mime
{
    protected $headers, $options;

    function __construct($headers, $options)
    {
        parent::__construct();

        $this->headers =& $headers;
        $this->options =& $options;

        empty($options['testMode']) || p::log('pMail-construct', $this->setTestMode());

        $this->setHeaders();


        if (!empty($options['attachments']) && is_array($options['attachments']))
        {
            $tmpToken = isset($options['attachments.tmpToken']) ? '~' . $options['attachments.tmpToken'] : false;

            foreach ($options['attachments'] as $name => $file)
            {
                if (!file_exists($file))
                {
                    user_error(__CLASS__ . ': file attachment not found (' . $file . ')');
                    continue;
                }

                is_int($name) && $name = '';

                $c_type = strtolower(strrchr($name ? $name : $file, '.'));
                $c_type = isset(p\StaticResource::$contentType[$c_type])
                    ? p\StaticResource::$contentType[$c_type]
                    : 'application/octet-stream';

                $this->addAttachment($file, $c_type, $name);

                $tmpToken
                    && $tmpToken === substr($file, -strlen($tmpToken))
                    && register_shutdown_function(array(__CLASS__, 'unlink'), $file);
            }
        }
    }

    protected function setHeaders()
    {
        $headers =& $this->headers;

        self::cleanHeaders($headers, 'Return-Path|From|Sender|Reply-To|Message-Id|To|Cc|Bcc|Subject');

        foreach (array('To', 'Cc', 'Bcc', 'Reply-To') as $sql)
        {
            isset($headers[$sql]) && is_array($headers[$sql]) && $headers[$sql] = implode(', ', $headers[$sql]);
        }

        $message_id = 'pM' . p::uniqId();

        $headers['Message-Id'] = '<' . $message_id . '@' . $_SERVER['HTTP_HOST']. '>';

        if (empty($headers['Sender']))
        {
            if ($CONFIG['pMail.sender']) $headers['Sender'] = $CONFIG['pMail.sender'];
        }

        if (empty($headers['From']))
        {
            if (empty($headers['Sender'])) user_error("Email is likely not to be sent: From header is empty.");
            else
            {
                $headers['From'] =& $headers['Sender'];
                unset($headers['Sender']);
            }
        }

        if (empty($headers['Return-Path']))
        {
                 if (isset($headers['Sender'])) $headers['Return-Path'] = $headers['Sender'];
            else if (isset($headers['From']  )) $headers['Return-Path'] = $headers['From'];
        }

        isset($headers['Return-Path'])
            && preg_match('/' . FILTER::EMAIL_RX . '/', $headers['Return-Path'], $m)
            && $headers['Return-Path'] = '<' . $m[0] . '>';
    }

    function send()
    {
        empty($this->options['html']) || $this->setHtmlBody($this->options['html']);
        empty($this->options['text']) || $this->setTxtBody( $this->options['text']);

        $body =& $this->get();
        $headers =& $this->headers($this->headers);

        $to = $headers['To'];
        unset($headers['To']);

        $options = null;
        $backend = $CONFIG['pMail.backend'];

        switch ($backend)
        {
        case 'mail':
            $options = $CONFIG['pMail.options'];
            isset($headers['Return-Path']) && $options .= ' -f ' . escapeshellarg(substr($headers['Return-Path'], 1, -1));
            break;

        case 'smtp':
            $options = $CONFIG['pMail.options'];
            break;
        }

        $mail = Mail::factory($backend, $options);
        $mail->send($to, $headers, $body);
    }

    function setTestMode()
    {
        $headers =& $this->headers;

        $log = array(
            'headers' => &$headers,
            'options' => &$this->options,
        );

        self::cleanHeaders($headers, 'To|Cc|Bcc');

        foreach (array('To', 'Cc', 'Bcc') as $sql)
        {
            if (isset($headers[$sql]))
            {
                $headers['X-Original-' . $sql] = is_array($headers[$sql])
                    ? implode(', ', $headers[$sql])
                    : $headers[$sql];

                unset($headers[$sql]);
            }
        }

        $headers['To'] = $CONFIG['pMail.debug_email'];

        return $log;
    }


    static function cleanHeaders(&$headers, $tpl)
    {
        $h = array();
        foreach ($headers as $k => $v) $h[strtolower($k)] = $k;

        foreach (explode('|', $tpl) as $v)
        {
            $k = strtolower(trim($v));
            if (isset($h[$k]) && $h[$k] !== $v)
            {
                $headers[$v] =& $headers[$h[$k]];
                unset($headers[$h[$k]]);
            }
        }
    }

    static function unlink($file)
    {
        @unlink($file);
    }
}
