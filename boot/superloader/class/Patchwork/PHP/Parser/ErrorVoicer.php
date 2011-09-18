<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class Patchwork_PHP_Parser_ErrorVoicer extends Patchwork_PHP_Parser
{
    protected

    $level = 0,
    $callbacks = array(
        'tagVoices' => array(T_EVAL, T_REQUIRE_ONCE, T_INCLUDE_ONCE, T_REQUIRE, T_INCLUDE)
    ),
    $bracketCallbacks = array(
        'incLevel' => array('(', '{', '[', '?'),
        'decLevel' => array(')', '}', ']', ':', ',', T_AS, T_CLOSE_TAG, ';'),
    );


    protected function tagVoices(&$token)
    {
        $e = T_EVAL === $token[0];
        $this->unregister($this->callbacks);
        $this->register($this->bracketCallbacks);
        $token[1] = (T_NS_SEPARATOR > 0 ? '\\' : '')
            . 'patchwork_error_voicer(error_reporting(error_reporting()&'
            . ~(($e ? E_PARSE : 0) | E_CORE_WARNING | E_COMPILE_WARNING) . '|'
            .  (($e ? 0 : E_PARSE) | E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR)
            . '),' . $token[1];
        $this->level = $e ? 0 : -1;
    }

    protected function incLevel(&$token)
    {
        ++$this->level;
    }

    protected function decLevel(&$token)
    {
        switch ($token[0])
        {
        case ',': if ($this->level) break;

        case ')':
        case '}':
        case ']':
        case ':': if ($this->level--) break;

        case ';':
        case T_AS:
        case T_CLOSE_TAG:
            $this->unregister($this->bracketCallbacks);
            $this->register($this->callbacks);
            return $token[1] = ')' . $token[1];
        }
    }
}
