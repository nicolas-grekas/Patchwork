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


Patchwork_PHP_Parser::createToken('T_ENDPHP'); // end of the source code


class Patchwork_PHP_Parser_Normalizer extends Patchwork_PHP_Parser
{
    protected

    $lfLineEndings = true,
    $checkUtf8     = true,
    $stripUtf8Bom  = true,
    $callbacks = array(
        'tagOpenEchoTag'  => T_OPEN_TAG_WITH_ECHO,
        'tagOpenTag'      => T_OPEN_TAG,
        'tagCloseTag'     => T_CLOSE_TAG,
        'fixVar'          => T_VAR,
        'tagHaltCompiler' => T_HALT_COMPILER,
    );


    protected function getTokens($code)
    {
        if ($this->lfLineEndings && false !== strpos($code, "\r"))
        {
            $code = str_replace("\r\n", "\n", $code);
            $code = strtr($code, "\r", "\n");
        }

        if ($this->checkUtf8 && !preg_match('//u', $code))
        {
            $this->setError("File encoding is not valid UTF-8", E_USER_WARNING);
        }

        if ($this->stripUtf8Bom && 0 === strncmp($code, "\xEF\xBB\xBF", 3))
        {
            // substr_replace() is for mbstring overloading resistance
            $code = substr_replace($code, '', 0, 3);
            $this->setError("Stripping UTF-8 Byte Order Mark", E_USER_NOTICE);
        }

        if ('' === $code) return array();

        $a = "\r" === $code[0]
            ? (isset($code[1]) && "\n" === $code[1] ? '\r\n' : '\r')
            : ("\n" === $code[0] ? '\n' : '');

        // Ensure that the first token is always a T_OPEN_TAG

        $code = '<?php ' . ($a ? "echo'{$a}'" : '') . '?'.">{$code}";
        $code = parent::getTokens($code);

        if (!$a && (T_OPEN_TAG === $code[2][0] || T_OPEN_TAG_WITH_ECHO === $code[2][0]))
        {
            $code[0] = $code[2];
            $code[1] = $code[2] = array(T_WHITESPACE, ' ');
        }

        // Ensure that the last valid PHP code position is tagged with a T_ENDPHP

        $a = array_pop($code);

        $code[] = T_CLOSE_TAG === $a[0] ? ';' : $a;
        T_INLINE_HTML === $a[0] && $code[] = array(T_OPEN_TAG, '<?php ');
        $code[] = array(T_ENDPHP, '');

        return $code;
    }

    protected function tagOpenEchoTag(&$token)
    {
        $this->tagOpenTag($token);

        return $this->unshiftTokens(
            array(T_OPEN_TAG, $token[1]),
            array(T_ECHO, 'echo'),
            array(T_WHITESPACE, ' ')
        );
    }

    protected function tagOpenTag(&$token)
    {
        $token[1] = substr_count($token[1], "\n");
        $token[1] = '<?php' . ($token[1] ? str_repeat("\n", $token[1]) : ' ');
    }

    protected function tagCloseTag(&$token)
    {
        $token[1] = substr_count($token[1], "\n");
        $token[1] = '?'.'>' . str_repeat("\n", $token[1]);
    }

    protected function fixVar(&$token)
    {
        return $this->unshiftTokens(array(T_PUBLIC, 'public'));
    }

    protected function tagHaltCompiler(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_HALT_COMPILER));
        return $this->unshiftTokens(array(T_ENDPHP, ''), ';', $token);
    }
}
