<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Parser;

use Patchwork\PHP\Parser;

/**
 * The ClosureShim parser participates in backporting closures to PHP 5.2.
 *
 * On PHP5.3+, it adds a warning when a variable is used both as an argument and a lexical.
 */
class ClosureShim extends Parser
{
    protected

    $closure = false,
    $closures = array(),
    $callbacks = array(
        'tagFunction' => T_FUNCTION,
        'tagEndPhp' => T_ENDPHP,
    ),
    $dependencies = 'ScopeInfo';


    protected function tagFunction(&$token)
    {
        $this->register('tagClosureArgs');
    }

    protected function tagClosureArgs(&$token)
    {
        if ('&' === $token[0]) return;
        $this->unregister(__FUNCTION__);
        if ('(' === $token[0])
        {
            $this->closure = array(
                'id' => sprintf('%010d', mt_rand()),
                'lines' => $this->line,
                'ref' => '&' === $this->prevType ? '&' : '',
                'signature' => '(',
                'args' => array(),
                'uses' => array(),
                'parent' => $this->closure,
            );

            if ($this->targetPhpVersionId < 50300)
            {
                if ('&' === end($this->types)) prev($this->types);
                prev($this->types);
                $this->texts[key($this->types)] .= "/*CLOSURE-START:{$this->closure['id']}*/";
            }

            $this->register(array(
                'tagUse' => T_USE,
                'tagClosureOpen' => '{',
                '~catchSignature',
            ));
        }
    }

    protected function catchSignature(&$token)
    {
        if (T_VARIABLE === $token[0])
        {
            $this->closure['args'][] = ('&' === $this->prevType ? '&' : '') . $token[1];
        }

        if ( $this->targetPhpVersionId < 50300
          && T_NS_SEPARATOR !== $token[0]
          && !isset($token['closure-stop'])
          && !isset($token[2][T_USE_NS]) )
        {
            $this->closure['signature'] .= $token[1];
        }
    }

    protected function tagUse(&$token)
    {
        $token['closure-stop'] = 1;
        $this->unregister(array('~catchSignature', 'tagUse' => T_USE));
        $this->register('tagLexicals');
    }

    protected function tagLexicals(&$token)
    {
        if (T_VARIABLE !== $token[0]) return;

        if ($this->targetPhpVersionId < 50300 && '$this' === $token[1])
        {
            $this->setError('Cannot use $this as lexical variable', E_USER_ERROR);
        }
        else if (array_intersect($this->closure['args'], array($token[1], '&' . $token[1])))
        {
            $this->setError("{$token[1]} is both an argument and a lexical variable", E_USER_WARNING);
        }

        if ($this->targetPhpVersionId < 50300)
        {
            $this->closure['uses'][] = ('&' === $this->prevType ? '&' : '') . $token[1];
        }
    }

    protected function tagClosureOpen(&$token)
    {
        $token['closure-stop'] = 1;
        $this->unregister(array(
            'tagUse' => T_USE,
            'tagClosureOpen' => '{',
            '~catchSignature',
            'tagLexicals',
        ));

        if ($this->targetPhpVersionId >= 50300)
        {
            $this->closure = $this->closure['parent'];
        }
        else
        {
            $token[1] = "/*CLOSURE-BODY:{$this->closure['id']}*/" . $token[1];
            $this->register(array('tagClosureClose' => T_BRACKET_CLOSE));
        }
    }

    protected function tagClosureClose(&$token)
    {
        $this->unregister(array('tagClosureClose' => T_BRACKET_CLOSE));
        $token[1] .= "/*CLOSURE-END:{$this->closure['id']}*/";
        $this->closure['lines'] .= '_' . $this->line;

        $c = $this->closure;
        $this->closure = $this->closure['parent'];
        unset($c['parent']);
        $this->closures[] = $c;
    }

    protected function tagEndPhp(&$token)
    {
        if (!$this->closures) return;

        $this->unregister($this->callbacks);

        $code = '';

        foreach ($this->closures as $c)
        {
            $name = "Closure_{$c['id']}_{$c['lines']}";
            $code .= "{$this->targetEol}class {$name} extends Closure{"
              .   "function {$c['ref']}__invoke{$c['signature']}{"
              .     "\$GLOBALS['i\x9D']=\$this;"
              .     "if(" . count($c['args']) . "<func_num_args()){"
              .       "\${1}=array(" . implode(',', $c['args']) . ")+func_get_args();"
              .       "return call_user_func_array('__{$name}_invoke',\${1});"
              .     "}"
              .     "return __{$name}_invoke(" . str_replace('&', '', implode(',', $c['args']))  . ");"
              .   "}"
              . "}";

            $code .= "{$this->targetEol}function {$c['ref']}__{$name}_invoke{$c['signature']}{"
              .   ($c['uses'] ? "extract(current(\$GLOBALS['i\x9D']),EXTR_REFS);" : '')
              .   "\$GLOBALS['i\x9D']=null;"
              .   "/*CLOSURE-HERE:{$c['id']}*/"
              . "}";
        }

        return $this->unshiftTokens(array(T_WHITESPACE, $code), $token);
    }

    function finalizeClosures($code)
    {
        foreach ($this->closures as $c)
        {
            if (preg_match("#/\*CLOSURE-START:{$c['id']}\*/.*?/\*CLOSURE-BODY:{$c['id']}\*/(.*?)/\*CLOSURE-END:{$c['id']}\*/#s", $code, $m))
            {
                foreach ($c['uses'] as &$u) $u = "'" . substr($u, '&' === $u[0] ? 2 : 1) . "'=>{$u}";

                $code = str_replace(
                    $m[0],
                    "(new Closure_{$c['id']}_{$c['lines']}"
                      .($c['uses'] ? "(array(" . implode(',', $c['uses'] ) . "))" : ''). ")"
                      . str_repeat($this->targetEol, substr_count($m[0], "\n")),
                    $code
                );
                $code = str_replace("/*CLOSURE-HERE:{$c['id']}*/", $m[1], $code);
            }
        }

        return $code;
    }
}
