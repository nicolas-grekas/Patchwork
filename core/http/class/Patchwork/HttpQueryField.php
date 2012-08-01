<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork;

/**
 * HttpQueryField provides a generalized access to HTTP inputs freed from the complexity of the magic bracketed syntax.
 *
 * See https://github.com/nicolas-grekas/Patchwork-Doc/blob/master/Advanced-HTTP-en.md
 */
class HttpQueryField
{
    protected

    $name,
    $selector;


    static function getNew($name)
    {
        return new self($name);
    }

    static function parseQuery($query)
    {
        $fields = array();

        foreach (explode('&', $query) as $query)
        {
            $query = explode('=', $query, 2);
            if ('' === $query[0]) continue;
            $query = array_map('urldecode', $query);
            $fields[$query[0]][] = isset($query[1]) ? $query[1] : '';
        }

        return $fields;
    }

    function __construct($name = null)
    {
        if (isset($name) && !$this->setName($name))
            throw new \Exception('Invalid field name');
    }

    function setName($name)
    {
        $this->selector = self::buildSelector($name);
        $this->name = isset($this->selector[1]) ? $name : null;
        return isset($this->selector[1]);
    }

    function getName()
    {
        return $this->name;
    }

    function &getValues(array $input)
    {
        return self::applySelector($this->selector, $input);
    }

    function &getFiles(array $input)
    {
        $o = array();
        $s = $this->selector;
        $s[0] && ++$s[0];
        isset($s[2]) && array_splice($s, 2, 0, '');

        foreach (array('name', 'type', 'tmp_name', 'error', 'size') as $s[2])
            foreach (self::applySelector($s, $input) as $i => $v)
                $o[$i][$s[2]] = $v;

        return $o;
    }

    protected static function buildSelector($name)
    {
        $s = rawurlencode($name);
        parse_str("{$s}&{$s}", $s);
        if (null === $n = key($s)) return array(0);
        $selector = array(0, $n);
        $s = $s[$n];

        while (is_array($s))
        {
            $n .= '[';
            if (2 === count($s)) $selector[0] = count($selector) - 1;
            else $n .= $selector[] = key($s);
            $n .= ']';
            $s = end($s);
        }

        return $n === $name ? $selector : array(0);
    }

    protected static function &applySelector($s, $input)
    {
        $o = array();
        $i = 0;

        while (isset($s[++$i]))
        {
            if (!isset($input[$s[$i]])) return $o;
            $input = $input[$s[$i]];
            if ($s[0] !== $i) continue;

            if (is_array($input))
            {
                for ($j = 0; isset($input[$j]); ++$j)
                {
                    $k = $i;
                    $v = $input[$j];

                    while (isset($s[++$k]))
                    {
                        if (!isset($v[$s[$k]])) continue 2;
                        $v = $v[$s[$k]];
                    }

                    is_scalar($v) && $o[] = $v;
                }
            }

            return $o;
        }

        is_scalar($input) && $o[] = $input;
        return $o;
    }
}
