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


class Patchwork_PHP_Parser_CurlyDollarNormalizer extends Patchwork_PHP_Parser
{
    protected

    $curly      = null,
    $curlyPool  = array(),
    $callbacks  = array('tagDollarCurly' => T_DOLLAR_OPEN_CURLY_BRACES);


    protected function tagDollarCurly(&$token)
    {
        $t =& $this->tokens;
        $i =  $this->index;

        if (!isset($t[$i], $t[$i+1]) || T_STRING_VARNAME !== $t[$i][0]) return;

        if ('}' === $t[$i+1][0] || '[' === $t[$i+1][0])
        {
            $t[$i] = array(T_VARIABLE, '$' . $t[$i][1]);
        }
        else
        {
            $t[$i] = array(T_CONSTANT_ENCAPSED_STRING, "'{$t[$i][1]}'");
            $this->unshiftTokens('$', '{');

            $this->curlyPool || $this->register($this->callbacks = array(
                'incCurly' => '{',
                'decCurly' => '}',
            ));

            $this->curlyPool[] = $this->curly;
            $this->curly = 0;
        }

        return $this->unshiftTokens(array(T_CURLY_OPEN, '{'));
    }

    protected function incCurly(&$token)
    {
        ++$this->curly;
    }

    protected function decCurly(&$token)
    {
        if (0 === --$this->curly)
        {
            $this->unshiftTokens('}');
            $this->curly = array_pop($this->curlyPool);
            if (null === $this->curly) $this->unregister();
        }
    }
}
