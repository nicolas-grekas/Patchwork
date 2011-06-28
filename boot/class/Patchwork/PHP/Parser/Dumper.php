<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
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

Patchwork_PHP_Parser::createToken('T_DUMPER_START');

class Patchwork_PHP_Parser_Dumper extends Patchwork_PHP_Parser
{
    public $codeWidth = 30;

    protected

    $token,
    $callbacks = 'startDumper';


    function startDumper($t)
    {
        echo $p = sprintf("% 4s: % -26s: % -{$this->codeWidth}s %s\n",
            'Line',
            'Token type',
            'Parsed code',
            'Sub-types'
        );
        echo str_repeat('=', strlen($p)), "\n";

        $this->unregister(__FUNCTION__);
        $this->register('dumpTokenStart');
        $this->dumpTokenStart($t);

        $p = new self($this);
        $p->unregister(__FUNCTION__);
        $p->register(array('dumpTokenEnd', 'dumpTokenEnd' => T_DUMPER_START));
        $p->token =& $this->token;

        return T_DUMPER_START;
    }

    function dumpTokenStart($t)
    {
        if (null !== $this->token) $this->dumpTokenEnd($this->token, true);

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

        if (strlen($t[1]) > $w && mb_strlen($t[1], 'UTF-8') > $w)
        {
            $t[1] = mb_substr($t[1], 0, $w - 1) . '…';
        }

        if ($canceled)
        {
            $canceled = '--- Canceled ---';
        }
        else
        {
            $canceled = '';
            $s = array_slice($t[2], 2);
            foreach ($s as $s) $canceled .= self::getTokenName($s) . ', ';
            '' !== $canceled && $canceled = substr($canceled, 0, -2);
        }

        echo sprintf("% 4s: % -26s: % -{$w}s %s\n",
            $this->token['line'],
            self::getTokenName($t[0]),
            $t[1],
            $canceled
        );

        $this->token = null;
    }
}
