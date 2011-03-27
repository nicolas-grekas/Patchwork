<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class loop_agentWrapper extends loop
{
    public $autoResolve = true;

    protected

    $agent,
    $keys;


    private

    $data,
    $firstCall = true;


    function __construct($agent, $keys = false)
    {
        $this->agent = $agent;
        if (false !== $keys) $this->keys = $keys;
    }

    final protected function prepare() {return 1;}
    final protected function next()
    {
        if ($this->firstCall)
        {
            $this->firstCall = false;
            if (!isset($this->data))
            {
                $data = $this->get();
                $data->{'a$'} = $this->agent;

                if ($this->autoResolve)
                {
                    if (!isset($this->keys) || preg_match("'^(/|https?://)'", $this->agent))
                    {
                        list($appId, $base, $data->{'a$'}, $keys, $a) = Patchwork\AgentTrace::resolve($this->agent);

                        foreach ($a as $k => &$v) $data->$k =& $v;

                        $data->{'k$'} = implode(',', $keys);

                        if (false !== $base)
                        {
                            $data->{'v$'} = $appId;
                            $data->{'r$'} = $base;
                        }
                    }
                    else $data->{'k$'} = $this->keys;
                }

                $this->data = $data;
            }

            return clone $this->data;
        }
        else
        {
            $this->firstCall = true;
            return false;
        }
    }

    protected function get()
    {
        return (object) array();
    }
}
