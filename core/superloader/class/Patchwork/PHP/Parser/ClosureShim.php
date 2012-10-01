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
 * The ClosureShim parser participates in backporting closures to PHP 5.2
 *
 * On PHP5.3+, it adds a warning when a variable is used both as an argument and a lexical variable
 */
class Patchwork_PHP_Parser_ClosureShim extends Patchwork_PHP_Parser
{
    protected

    $closureIndex,
    $bracket = 0,
    $closure = false,
    $closures = array(),
    $callbacks = array(
        'tagFunction' => T_FUNCTION,
        'tagEndPhp' => T_ENDPHP,
    );


    protected function tagFunction(&$token)
    {
        $this->closureIndex = $this->index;
        $this->register('tagClosureArgs');
    }

    protected function tagClosureArgs(&$token)
    {
        if ('&' === $token[0]) return;
        $this->unregister(__FUNCTION__);
        if ('(' === $token[0])
        {
            $this->bracket = 1;
            $this->closure = array(
                'name' => sprintf('Closure_%010d_%s', mt_rand(), $this->line),
                'ref' => '&' === $this->prevType ? '&' : '',
                'signature' => '(',
                'args' => array(),
                'uses' => array(),
                'index' => $this->closureIndex + 1,
                'body' => '',
                'parent' => $this->closure,
            );
            $this->register('~catchSignature');
        }
    }

    protected function catchSignature(&$token)
    {
        if (T_VARIABLE === $token[0])
        {
            $this->closure['args'][] = ('&' === $this->prevType ? '&' : '') . $token[1];
        }
        else if ('(' === $token[0]) ++$this->bracket;
        else if (')' === $token[0]) --$this->bracket;

        $this->closure['signature'] .= $token[1];

        if ($this->bracket <= 0)
        {
            $this->unregister('~catchSignature');
            $this->register('tagUse');
            $this->register(array('tagClosureOpen' => '{'));
        }
    }

    protected function tagUse(&$token)
    {
        if ($this->bracket <= 0)
        {
            if (T_USE === $token[0])
            {
                $this->bracket = 1;
                return;
            }
        }
        else
        {
            if (T_VARIABLE === $token[0])
            {
                if (PHP_VERSION_ID < 50300 && '$this' === $token[1])
                {
                    $this->setError('Cannot use $this as lexical variable', E_USER_ERROR);
                }
                else if (array_intersect($this->closure['args'], array($token[1], '&' . $token[1])))
                {
                    $this->setError("{$token[1]} is both an argument and a lexical variable", E_USER_WARNING);
                }

                $this->closure['uses'][] = ('&' === $this->prevType ? '&' : '') . $token[1];
            }
            else if ('(' === $token[0]) ++$this->bracket;
            else if (')' === $token[0]) --$this->bracket;

        }

        if ($this->bracket <= 1)
        {
            $this->unregister(__FUNCTION__);
        }
    }

    protected function tagClosureOpen(&$token)
    {
        $this->unregister(array(__FUNCTION__ => '{'));
        $this->register(array('tagClosureClose' => T_BRACKET_CLOSE));
    }

    protected function tagClosureClose(&$token)
    {
        $this->closure['name'] .= '_' . $this->line;
        $this->unregister('tagClosureClose');
        $this->register('tagAfterClosure');
    }

    protected function tagAfterClosure(&$token)
    {
        $this->unregister('tagAfterClosure');

        $c = $this->closure;
        $this->closure = $this->closure['parent'];

        if (PHP_VERSION_ID >= 50300) return;

        unset($c['parent']);
        $is_body = false;

        for ($i = $c['index']; $i <= $this->index; ++$i)
        {
            $u =& $this->texts[$i];
            if ($is_body) $c['body'] .= $u;
            else if (isset($this->types[$i])) $is_body = '{' === $this->types[$i];
            if (false !== strpos($u, '*/')) $u = str_replace('*/', '//', $u);
        }

        $u .= '*/';

        foreach ($c['uses'] as &$u) $u = "'" . substr($u, '&' === $u[0] ? 2 : 1) . "'=>{$u}";

        $u =& $this->texts[$c['index']];
        $u = "new {$c['name']}(" . ($c['uses'] ? "array(" . implode(',', $c['uses']) . ")" : '') . ")/*{$u}";

        $this->closures[] = $c;
    }

    protected function tagEndPhp(&$token)
    {
        if (!$this->closures) return;

        $this->unregister($this->callbacks);

        $code = '';

        foreach ($this->closures as $c)
        {
            $code .= "\nclass {$c['name']} extends Closure{"
              .   "function {$c['ref']}__invoke{$c['signature']}{"
              .     "\$GLOBALS['i\x9D']=\$this;"
              .     "if(" . count($c['args']) . "<func_num_args()){"
              .       "\${1}=array(" . implode(',', $c['args']) . ")+func_get_args();"
              .       "return call_user_func_array('__{$c['name']}_invoke',\${1});"
              .     "}"
              .     "return __{$c['name']}_invoke(" . str_replace('&', '', implode(',', $c['args']))  . ");"
              .   "}"
              . "}";

            $code .= "\nfunction {$c['ref']}__{$c['name']}_invoke{$c['signature']}{"
              . ($c['uses'] ? "extract(current(\$GLOBALS['i\x9D']),EXTR_REFS);" : '')
              . "\$GLOBALS['i\x9D']=null;"
              . "{$c['body']}";
        }

        $this->closures = array();

        return $this->unshiftTokens(array(T_WHITESPACE, $code), $token);
    }
}
