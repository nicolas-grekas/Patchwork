<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


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
