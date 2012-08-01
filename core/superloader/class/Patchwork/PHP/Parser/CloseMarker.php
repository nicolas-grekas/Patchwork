<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


// TODO: replace this catch-all filterToken by fine grained token registration

class Patchwork_PHP_Parser_CloseMarker extends Patchwork_PHP_Parser
{
    protected

    $openToken,
    $curly,
    $open,
    $close,
    $greedy = false,
    $bracket = 0,
    $callbacks = 'filterToken',
    $registered = true;


    function __construct(parent $parent, &$openToken, $curly, $open, $close)
    {
        $this->openToken =& $openToken;
        $this->curly = $curly;
        $this->open = $open;
        $this->close = $close;
        parent::__construct($parent);
    }

    protected function filterToken(&$token)
    {
        if ($this->greedy) return $this->greedyFilter($token);

        if (0 <= $this->curly) switch ($token[0])
        {
            case '$': break;
            case '{': ++$this->curly; break;
            case '}': --$this->curly; break;
            default: 0 < $this->curly || $this->curly = -1;
        }
        else
        {
            if ('?' === $token[0]) --$this->bracket;
            $this->greedyFilter($token);
            if (':' === $token[0]) ++$this->bracket;

            if (0 < $this->bracket || !$this->registered) return;

            switch ($token[0])
            {
            case ')':
            case '}':
            case ']':
            case T_INC:
            case T_DEC:
            case T_STRING:
            case T_NS_SEPARATOR:
                break;

            case T_OBJECT_OPERATOR:
                $this->curly = 0;

            case '=':
            case T_DIV_EQUAL:
            case T_MINUS_EQUAL:
            case T_MOD_EQUAL:
            case T_MUL_EQUAL:
            case T_PLUS_EQUAL:
            case T_SL_EQUAL:
            case T_SR_EQUAL:
            case T_XOR_EQUAL:
            case T_AND_EQUAL:
            case T_OR_EQUAL:
            case T_CONCAT_EQUAL:
                $this->greedy = true;
                break;

            default:
                $this->openToken = $this->open . $this->openToken;
                $token[1] = $this->close . $token[1];
                $this->unregister($this->callbacks);
            }
        }
    }

    protected function greedyFilter(&$token)
    {
        switch ($token[0])
        {
        case '(':
        case '{':
        case '[':
        case '?': ++$this->bracket; break;
        case ',': if ($this->bracket) break;
        case ')':
        case '}':
        case ']':
        case ':': if ($this->bracket--) break;
        case T_AS: case T_CLOSE_TAG: case ';':
            $this->openToken = $this->open . $this->openToken;
            $token[1] = $this->close . $token[1];
            $this->unregister($this->callbacks);
            $this->registered = false;
        }
    }
}
