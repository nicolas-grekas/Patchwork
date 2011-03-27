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


// TODO: replace this catch-all filterToken by fine grained token registration

class Patchwork_PHP_Parser_CloseMarker extends Patchwork_PHP_Parser
{
    protected

    $close,
    $greedy = false,
    $bracket = 0,
    $curly = 0,
    $callbacks = 'filterToken',
    $registered = true;


    function __construct(parent $parent, $curly, $close = ':0)')
    {
        $this->curly = $curly;
        $this->close = $close;
        parent::__construct($parent);
    }

    protected function unregister($method = null)
    {
        parent::unregister($method);
        $this->registered = null !== $method;
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
                $token[1] = $this->close . $token[1];
                $this->unregister();
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
            $token[1] = $this->close . $token[1];
            $this->unregister();
        }
    }
}
