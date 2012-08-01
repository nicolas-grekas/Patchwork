<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

/**
 * The Dumper parser helps understanding how parsers work by displaying tokens as they come up.
 */
class Patchwork_PHP_Parser_Dumper extends Patchwork_PHP_Parser
{
    public

    $codeWidth = 30,
    $encoding = 'UTF-8',
    $placeholders = array('…', '∅', '␣', '⏎');

    protected

    $token,
    $nextIndex = 0,
    $callbacks = array(
        'startDumper',
        'dumpTokenStart',
        '~dumpTokenEnd',
    );


    function startDumper($t)
    {
        echo $p = sprintf("% 4s % {$this->codeWidth}s % -{$this->codeWidth}s %s\n",
            'Line',
            'Source code',
            'Parsed code',
            'Token type(s)'
        );
        echo str_repeat('=', mb_strlen($p, $this->encoding)), "\n";

        $this->unregister(__FUNCTION__);
    }

    function dumpTokenStart($t)
    {
        null !== $this->token && $this->dumpTokenEnd($this->token, true);

        $this->index <= $this->nextIndex
            ? $t[1] = ' --- inserted --- '
            : $this->nextIndex = $this->index;

        $t['line'] = $this->line;
        $this->token = $t;
    }

    function dumpTokenEnd($t, $canceled = false)
    {
        if ($this->token[0] !== $t[0])
        {
            $this->setError(
                sprintf("Token has mutated from %s to %s", self::getTokenName($this->token[0]), self::getTokenName($t[0])),
                E_USER_WARNING
            );
        }

        $w = $this->codeWidth;
        $p = $this->placeholders;

        if (strlen($this->token[1]) > $w && mb_strlen($this->token[1], $this->encoding) > $w)
        {
            $this->token[1] = mb_substr($this->token[1], 0, $w - 1, $this->encoding) . $p[0];
        }

        if ($canceled)
        {
            $t[1] = ' --- canceled --- ';
            $canceled = self::getTokenName($t[0]);
        }
        else
        {
            if (strlen($t[1]) > $w && mb_strlen($t[1], $this->encoding) > $w)
            {
                $t[1] = mb_substr($t[1], 0, $w - 1, $this->encoding) . $p[0];
            }

            $canceled = '';
            $s = array_slice($t[2], 1);
            foreach ($s as $s) $canceled .= self::getTokenName($s) . ', ';
            '' !== $canceled && $canceled = substr($canceled, 0, -2);
        }

        $w = array(
            $w, $this->token[1],
            $w, $this->token[1] !== $t[1] ? ('' === trim($t[1]) ? ('' === $t[1] ? $p[1] : str_replace(' ', $p[2], $t[1])) : $t[1]) : '',
        );

        $w[0] += strlen($w[1]) - mb_strlen($w[1], $this->encoding);
        $w[2] += strlen($w[3]) - mb_strlen($w[3], $this->encoding);

        echo str_replace(
            array("\r\n", "\n", "\r"),
            array($p[3], $p[3], $p[3]),
                sprintf("% 4s % {$w[0]}s % -{$w[2]}s %s",
                $this->token['line'],
                $w[1],
                $w[3],
                $canceled
            )
        ) . "\n";

        $this->token = null;
    }
}
