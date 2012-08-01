<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


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
