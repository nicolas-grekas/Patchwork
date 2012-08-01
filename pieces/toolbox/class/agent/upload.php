<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


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
