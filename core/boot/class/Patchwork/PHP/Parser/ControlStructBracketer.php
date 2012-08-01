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
 * The ControlStructBracketer parser adds curly brackets around controle structures' code blocks.
 */
class Patchwork_PHP_Parser_ControlStructBracketer extends Patchwork_PHP_Parser
{
    protected

    $cntrlStack = array(),
    $blockStack = array(),
    $lastCntrl = false,
    $callbacks = array(
        '~tagControl' => array(T_FOR, T_FOREACH, T_DO, T_WHILE, T_IF, T_ELSEIF, T_ELSE, T_SWITCH),
        '~tagControlEnd' => array(T_ENDFOR, T_ENDFOREACH, T_ENDWHILE, T_ENDIF, T_ENDSWITCH),
        '~tagSemicolon' => ';',
    ),

    $brackets,
    $dependencies = array('BracketWatcher' => 'brackets');


    protected function tagControl(&$token)
    {
        switch ($token[0])
        {
        case T_ELSE:
        case T_DO:
            $this->register('tagBlockOpen');
            break;

        case T_WHILE:
            if (T_DO === $this->lastCntrl)
            {
                $this->lastCntrl = false;
                return;
            }
            // No break;
        default:
            $this->register(array('~tagConditionClose' => T_BRACKET_CLOSE));
        }

        $this->cntrlStack[] = $token[0];
    }

    protected function tagConditionClose(&$token)
    {
        $this->register('tagBlockOpen');
    }

    protected function tagBlockOpen(&$token)
    {
        $this->unregister(__FUNCTION__);

        if (T_IF === $token[0] && T_ELSE === $this->prevType)
        {
            array_pop($this->cntrlStack);
            return;
        }

        if (isset($token[0][0])) switch ($token[0])
        {
        case '{': $this->register(array('tagBlockClose' => T_BRACKET_CLOSE));
        case ':': $this->blockStack[] = -1;
            return;
        }

        $this->blockStack[] = count($this->brackets) + 1;
        return $this->unshiftTokens('{', $token);
    }

    protected function tagControlEnd(&$token)
    {
        array_pop($this->blockStack);
        array_pop($this->cntrlStack);
    }

    protected function tagSemicolon(&$token)
    {
        if (count($this->brackets) === end($this->blockStack))
        {
            $this->register('tagBlockClose');
            $this->unshiftTokens('}');
        }
    }

    protected function tagBlockClose(&$token)
    {
        $this->unregister(array('tagBlockClose', 'tagBlockClose' => T_BRACKET_CLOSE));

        $offset = 0;
        $token =& $this->getNextToken();

        for (;;)
        {
            array_pop($this->blockStack);

            switch ($this->lastCntrl = array_pop($this->cntrlStack))
            {
            case T_IF:
            case T_ELSEIF:
                if (T_ELSEIF !== $token[0] && T_ELSE !== $token[0]) break;
            case T_DO: break 2;
            }

            switch (count($this->brackets) - ++$offset)
            {
            case 0:
            default: break 2;
            case end($this->blockStack): $this->unshiftTokens('}');
            }
        }
    }
}
