<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pTask_periodic extends pTask
{
    static

    $days   = array(0 => 'sun','mon','thu','wed','tue','fri','sat'),
    $months = array(1 => 'jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec');


    protected

    $crontab = array(),
    $finalRun = 0,
    $runLimit = -1;


    static function __init()
    {
        self::$days   = array(self::$days,   array_keys(self::$days  ));
        self::$months = array(self::$months, array_keys(self::$months));
    }

    function __construct($crontab, $callback = false, $arguments = array())
    {
        $this->setCrontab($crontab);
        parent::__construct($callback, $arguments);
    }

    function setCrontab($crontab)
    {
        is_array($crontab) && $crontab = implode("\n", $crontab);
        $crontab = strtr($crontab, '|', "\n");
        $crontab = strtolower(trim($crontab));
        false !== strpos($crontab, "\r")  && $crontab = strtr(str_replace("\r\n", "\n", $crontab), "\r", "\n");

        $c = explode("\n", $crontab);
        $crontab = array();

        foreach ($c as &$cronline)
        {
            $cronline = trim($cronline);
            $cronline = preg_split('/\s+/', $cronline);

            if ('' === $cronline[0]) continue;

            $i = 5;
            while (!isset($cronline[--$i])) $cronline[$i] = '*';

            $cronline[3] = str_replace(self::$months[0], self::$months[1], $cronline[3]);
            $cronline[4] = str_replace(self::$days[0],   self::$days[1],   $cronline[4]);

            $cronline[0] = self::expandCrontabItem($cronline[0], 0, 59);
            $cronline[1] = self::expandCrontabItem($cronline[1], 0, 23);
            $cronline[2] = self::expandCrontabItem($cronline[2], 1, 31);
            $cronline[3] = self::expandCrontabItem($cronline[3], 1, 12);
            $cronline[4] = self::expandCrontabItem($cronline[4], 0, 6 );

            $crontab[] =& $cronline;
        }

        $this->crontab =& $crontab;

        return $this;
    }

    function setFinalRun($time)
    {
        $this->finalRun = (int) $time;
        return $this;
    }

    function setRunLimit($count)
    {
        $this->runLimit = (int) $count;
        return $this;
    }


    function getNextRun($time = false)
    {
        if (!$this->runLimit || !$this->crontab
            || (0 < $this->finalRun && $this->finalRun <= $_SERVER['REQUEST_TIME']))
        {
            return 0;
        }

        $this->runLimit > 0 && --$this->runLimit;

        $time = getdate(false !== $time ? $time : $_SERVER['REQUEST_TIME']);

        $nextRun = 0;

        foreach ($this->crontab as &$cronline)
        {
            $next = array($time['minutes'], $time['hours'], $time['mday'], $time['mon'], $time['year']);

            switch (true)
            {
            case !in_array($next[3], $cronline[3]): $next[2] = 1;
            case !in_array($next[2], $cronline[2]): $next[1] = 0;
            case !in_array($next[1], $cronline[1]): $next[0] = 0; break;
            case  in_array($next[0], $cronline[0]): ++$next[0];
            }

            for ($n = 0; $n < 4; ++$n) self::putNextTick($next, $cronline, $n);

            while (($n = mktime($next[1], $next[0], 0, $next[3], $next[2], $next[4]))
                && (!$nextRun || $n < $nextRun)
                && !in_array(idate('w', $n), $cronline[4]))
            {
                ++$next[2];
                $next[0] = $cronline[0][0];
                $next[1] = $cronline[1][0];
                self::putNextTick($next, $cronline, 2);
                self::putNextTick($next, $cronline, 3);
            }

            if (!$nextRun || $n < $nextRun) $nextRun = $n;
        }

        return $nextRun;
    }


    protected static function expandCrontabItem($cronitem, $min, $max)
    {
        if ('*' == $cronitem[0]) $cronitem = $min . '-' . $max . substr($cronitem, 1);

        $width = $max - $min + 1;
        $cronitem = explode(',', $cronitem);

        $list = array();

        foreach ($cronitem as $i)
        {
            if (preg_match('#^(\d+)(?:-(\d+)((?:[~/]\d+)*))?$#', $i, $item))
            {
                $item[1] = ($item[1] - $min) % $width + $min;

                if (isset($item[2]))
                {
                    $item[2] = ($item[2] - $min) % $width + $min;
                    if ($item[2] < $item[1]) $item[2] += $width;

                    $range = range($item[1], $item[2]);
                    foreach ($range as &$i) $i = ($i - $min) % $width + $min;
                    unset($i);

                    if (isset($item[3]))
                    {
                        $item = preg_split('#([~/])#', $item[3], -1, PREG_SPLIT_DELIM_CAPTURE);
                        $len = count($item);
                        for ($i = 2; $i < $len; $i+=2)
                        {
                            if ('~' == $item[$i-1])
                            {
                                $item[$i] = ($item[$i] - $min) % $width + $min;
                                $range = array_diff($range, array($item[$i]));
                            }
                            else
                            {
                                $item[$i] = (int) $item[$i];

                                $range2 = array();
                                for ($j = 0; isset($range[$j]); $j += $item[$i]) $range2[] = $range[$j];
                                $range = $range2;
                            }
                        }
                    }

                    $range || $range = range($min, $max);

                    $list = array_merge($list, $range);
                }
                else $list[] = $item[1];
            }
            else user_error("Invalid crontab item: " . $i);
        }

        $list = array_keys(array_flip($list));
        sort($list);

        return $list;
    }

    protected static function putNextTick(&$next, &$list, $index)
    {
        $list =& $list[$index];

        $len = count($list);
        for ($i = 0; $i < $len && $list[$i] < $next[$index]; ++$i) {}

        if ($i == $len)
        {
            $i = 0;
            ++$next[$index + 1];
            $next = getdate(mktime($next[1], $next[0], 0, $next[3], $next[2], $next[4]));
            $next = array($next['minutes'], $next['hours'], $next['mday'], $next['mon'], $next['year']);
        }

        $next[$index] = $list[$i];
    }
}
