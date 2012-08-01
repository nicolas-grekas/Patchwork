<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

Patchwork_PHP_Parser::createToken('T_ENDPHP');

/**
 * The Normalizer parser verifies and ensures basic guaranties on the parsed code and its token stream.
 *
 * On the raw code, it can verify its UTF-8 validity,
 * strip any UTF-8 BOM and force line endings to LF only.
 * On the token stream, it enforces the very first token to be a T_OPEN_TAG
 * and tags the last valid PHP code position as T_ENDPHP.
 */
class Patchwork_PHP_Parser_Normalizer extends Patchwork_PHP_Parser
{
    protected

    $checkUtf8 = true,
    $stripUtf8Bom = true,
    $lfLineEndings = true,
    $callbacks = array(
        'fixVar' => T_VAR,
        'tagOpenTag' => T_OPEN_TAG,
        'tagCloseTag' => T_CLOSE_TAG,
        'tagOpenEchoTag' => T_OPEN_TAG_WITH_ECHO,
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

        $code = parent::getTokens($code);

        // Ensure that the first token is always a T_OPEN_TAG

        if (T_INLINE_HTML === $code[0][0])
        {
            $a = $code[0][1];
            $a = "\r" === $a[0]
                ? (isset($a[1]) && "\n" === $a[1] ? '\r\n' : '\r')
                : ("\n" === $a[0] ? '\n' : '');

            if ($a)
            {
                array_unshift($code,
                    array(T_OPEN_TAG, '<?php '),
                    array(T_ECHO, 'echo'),
                    array(T_ENCAPSED_AND_WHITESPACE, "\"{$a}\""),
                    array(T_CLOSE_TAG, '?>')
                );
            }
            else
            {
                array_unshift($code,
                    array(T_OPEN_TAG, '<?php '),
                    array(T_CLOSE_TAG, '?>')
                );
            }
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
