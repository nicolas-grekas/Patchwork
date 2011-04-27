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

require dirname(dirname(__FILE__)) . '/PHP/Parser.php';
require dirname(dirname(__FILE__)) . '/PHP/Parser/Normalizer.php';
require dirname(dirname(__FILE__)) . '/PHP/Parser/Scream.php';
require dirname(dirname(__FILE__)) . '/PHP/Parser/StaticState.php';


class Patchwork_Bootstrapper_Preprocessor
{
    protected $parser;

    function staticPass1($code, $file)
    {
        if ('' === $code) return '';

        $p = new Patchwork_PHP_Parser_Normalizer;
        $p = $this->parser = new Patchwork_PHP_Parser_StaticState($p);

        if( (defined('DEBUG') && DEBUG)
            && !empty($GLOBALS['CONFIG']['debug.scream'])
                || (defined('DEBUG_SCREAM') && DEBUG_SCREAM) )
        {
            new Patchwork_PHP_Parser_Scream($p);
        }

        $code = $p->getRunonceCode($code);

        if ($p = $p->getErrors())
        {
            $p = $p[0];
            $p = addslashes("{$p[0]} in {$file}") . ($p[1] ? " on line {$p[1]}" : '');

            $code .= "die('Patchwork error: {$p}');";
        }

        return $code;
    }

    function staticPass2()
    {
        if (empty($this->parser)) return '';
        $code = substr($this->parser->getRuntimeCode(), 5);
        $this->parser = null;
        return $code;
    }
}
