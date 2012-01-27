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


class agent_upload extends agent
{
    public $get = 'id';

    function control() {}

    function compose($o)
    {
        if ($this->get->id)
        {
            $this->expires = 'onmaxage';
            Patchwork::setPrivate();

            if (function_exists('upload_progress_meter_get_info'))
            {
                $o = (object) @upload_progress_meter_get_info($this->get->id);
            }
            else if (function_exists('uploadprogress_get_info'))
            {
                $o = (object) @uploadprogress_get_info($this->get->id);
            }
        }
        else $this->maxage = -1;

        return $o;
    }
}
