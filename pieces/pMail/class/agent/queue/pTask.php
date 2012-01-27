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


class agent_queue_pTask extends self
{
    function ob_handler($buffer)
    {
        parent::ob_handler($buffer);

        if ('' !== $buffer && $CONFIG['pMail.debug_email'])
        {
            $m = new pMail_text(
                array('To'   => $CONFIG['pMail.debug_email']),
                array('text' => $buffer)
            );
            $m->send();
        }

        return '';
    }
}
