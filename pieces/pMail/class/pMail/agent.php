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

class pMail_agent extends pMail_text
{
    protected

    $agent,
    $args,
    $lang,

    $addedImage = array();


    static protected $imageCache = array();


    function __construct($headers, $options)
    {
        $this->agent = $options['agent'];
        $this->args  = $options['args'];
        $this->lang  = isset($options['lang']) ? $options['lang'] : p::__LANG__();

        parent::__construct($headers, $options);
    }

    function send()
    {
        $html = p\Serverside::returnAgent($this->agent, $this->args, $this->lang);

        if (!isset($this->headers['Subject']) && preg_match("'<title[^>]*>(.*?)</title[^>]*>'isu", $html, $title))
        {
            $this->headers['Subject'] = trim(html_entity_decode($title[1], ENT_COMPAT, 'UTF-8'));
        }


        // HTML cleanup

        // Remove noisy tags
        $html = preg_replace('#<(head|script|title|applet|frameset|i?frame)\b[^>]*>.*?</\1\b[^>]*>#is', '', $html);
        $html = preg_replace('#</?(?:!doctype|html|meta|body|base|link)\b[^>]*>#is', '', $html);
        $html = preg_replace('#<!--.*?-->#s', '', $html);
        $html = trim($html);

        // Clean up URLs in attributes
        $html = preg_replace_callback(
            '/(\s)(src|background|href)\s*=\s*(["\'])?((?(3).*?|[^\s>]*))(?(3)\3)/iu',
            array($this, 'cleanUrlAttribute'),
            $html
        );

        if (!empty($this->options['embedImages']))
        {
            // Embed images
            $html = preg_replace_callback(
                '/(\s)(src|background)="([^"]+\.(jpe?g|png|gif))"/iu',
                array($this, 'addRawImage'),
                $html
            );
        }

        $this->options['html'] =& $html;


        // HTML to text conversion

        $c = new converter_txt_html(78);
        $this->options['text'] = $c->convertData($html);


        parent::send();
    }

    protected function cleanUrlAttribute($m)
    {
        return $m[1] . $m[2] . '="' . str_replace('"', '&quot;', p::base($m[4], true)) . '"';
    }

    protected function addRawImage($m)
    {
        $url = $m[3];

        if (isset($this->addedImage[$url])) return $m[0];

        if (isset(self::$imageCache[$url])) $data =& self::$imageCache[$url];
        else
        {
            if (ini_get_bool('allow_url_fopen')) $data = file_get_contents($url);
            else
            {
                $data = new HTTP_Request($url);
                $data->sendRequest();
                $data = $data->getResponseBody();
            }

            self::$imageCache[$url] =& $data;
        }

        switch (strtolower($m[4]))
        {
            case 'png': $mime = 'image/png'; break;
            case 'gif': $mime = 'image/gif'; break;
            default: $mime = 'image/jpeg';
        }

        $this->addHtmlImage($data, $mime, $url, false);

        $this->addedImage[$url] = true;

        return $m[0];
    }

    function setTestMode()
    {
        $log = parent::setTestMode();
        $lang = p::setLang($this->lang);

        $url = p::base($this->agent, true);
        empty($this->args) || $url .= '?' . http_build_query($this->args);

        p::setLang($lang);
        $log['test-url'] = $url;

        return $log;
    }
}
