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


class Patchwork_PHP_Parser_Bracket_T extends Patchwork_PHP_Parser_Bracket
{
    protected $onOpenCallbacks = array(
        'tagConcatenation' => array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES, '.'),
    );

    protected function tagConcatenation(&$token)
    {
        $this->setError("Usage of T() is potentially divergent, please avoid string concatenation", E_USER_NOTICE);
        $this->unregister();
    }
}
