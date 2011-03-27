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


class Patchwork_PHP_Parser_Bracket_ClassExists extends Patchwork_PHP_Parser_Bracket
{
    protected function onReposition(&$token)
    {
        if (1 === $this->bracketIndex) $token[1] .= '(';
        if (2 === $this->bracketIndex) $token[1] = ')||1' . $token[1];
    }

    protected function onClose(&$token)
    {
        if (1 === $this->bracketIndex) $token[1] = ')||1' . $token[1];
    }
}
