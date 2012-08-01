<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pMail_template extends pMail_agent
{
    function __construct($headers, $options)
    {
        $options['agent'] = 'outerData';
        $options['args']  = array();

        parent::__construct($headers, $options);
    }

    function send()
    {
        agent_outerData::$outerData     = $this->options['data'];
        agent_outerData::$outerTemplate = $this->options['template'];

        return parent::send();
    }

    function setTestMode()
    {
        pMail_text::setTestMode();
    }
}
