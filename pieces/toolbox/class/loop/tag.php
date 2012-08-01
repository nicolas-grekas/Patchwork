<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class loop_tag extends loop
{
    protected

    $loop,
    $freqKey,
    $sizeKey,
    $range,

    $histo,
    $min,
    $max,

    $histoCumule = array(),
    $dynamic;


    function __construct($loop, $freqKey, $sizeKey, $range)
    {
        $this->loop = $loop;
        $this->freqKey = $freqKey;
        $this->sizeKey = $sizeKey;
        $this->range = $range;
    }

    function setHisto($histo, $min, $max)
    {
        $this->histo = $histo;
        $this->min = $min;
        $this->max = $max;
    }

    protected function prepare()
    {
        $loop = $this->loop;

        if (!$this->histoCumule)
        {
            $histo =& $this->histo;
            $min =& $this->min;
            $max =& $this->max;

            if (!isset($histo))
            {
                $histo = array();
                $min = -1;
                $max = 0;

                while ($a = $loop->loop())
                {
                    $a = $a->{$this->freqKey};

                    if (isset($histo[$a])) ++$histo[$a];
                    else $histo[$a] = 1;

                    $min = $min < 0 ? $a : min($min, $a);
                    $max = max($max, $a);
                }
            }

            $histoCumule = array( $min - 1 => 0 );
            $dynamic = $max - $min;

            if ($dynamic > 0)
            {
                $histo[$min] = 0;

                do $histoCumule[$min] = $histoCumule[$min - 1] + (isset($histo[$min]) ? $histo[$min] : 0);
                while (++$min <= $max);

                $this->dynamic = 1 / ($histoCumule[$max - 1] + $histoCumule[$max] + 1);
            }
            else $this->dynamic = 0;

            $this->histoCumule =& $histoCumule;

            unset($this->histo);
        }

        return $loop->getLength();
    }

    protected function next()
    {
        $a = $this->loop->loop();

        if ($a)
        {
            $b = $a->{$this->freqKey};
            $a->{$this->sizeKey} = $this->dynamic
                ? intval($this->range * $this->dynamic * ($this->histoCumule[$b-1] + $this->histoCumule[$b]))
                : $this->range;
        }

        return $a;
    }
}
