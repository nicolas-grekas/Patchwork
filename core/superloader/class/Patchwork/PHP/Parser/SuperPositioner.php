<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class Patchwork_PHP_Parser_SuperPositioner extends Patchwork_PHP_Parser
{
    protected

    $level,
    $topClass,
    $exprLevel,
    $exprCallbacks = array(
        '~incExprLevel' => array('(', '{', '[', '?'),
        'decExprLevel' => array(')', '}', ']', ':', ',', T_AS, T_CLOSE_TAG, ';'),
    ),
    $callbacks = array(
        'tagClassUsage'  => array(T_USE_CLASS, T_TYPE_HINT),
        'tagClass'       => T_CLASS,
        'tagClassName'   => T_NAME_CLASS,
        'tagPrivate'     => T_PRIVATE,
        'tagRequire'     => array(T_REQUIRE_ONCE, T_INCLUDE_ONCE, T_REQUIRE, T_INCLUDE),
        'tagSpecialFunc' => T_USE_FUNCTION,
    ),

    $class, $namespace, $nsResolved, $nsPrefix, $expressionValue,
    $dependencies = array(
        'ClassInfo' => array('class', 'namespace', 'nsResolved', 'nsPrefix'),
        'ConstantExpression' => 'expressionValue',
    );


    function __construct(parent $parent, $level, $topClass)
    {
        if (0 <= $level) unset($this->callbacks['tagRequire']);

        parent::__construct($parent);
        $this->level    = $level;
        $this->topClass = $topClass;
    }

    protected function tagClassUsage(&$token)
    {
        switch ($token[1])
        {
        case 'self':   if (empty($this->class->name   )) return; $c = $this->class->nsName;  break;
        case 'parent': if (empty($this->class->extends)) return; $c = $this->class->extends; break;
        }

        if (empty($c) || $this->nsPrefix)
        {
            if (isset($token[2][T_USE_CLASS])
                && 0 === strcasecmp('\ReflectionClass', $this->nsResolved)
                && (!$this->class || strcasecmp('Patchwork_PHP_ReflectionClass', strtr($this->class->nsName, '\\', '_'))))
            {
                $this->unshiftTokens(
                    array(T_STRING, 'Patchwork'),
                    array(T_NS_SEPARATOR, '\\'),
                    array(T_STRING, 'PHP'),
                    array(T_NS_SEPARATOR, '\\'),
                    array(T_STRING, 'ReflectionClass')
                );

                $this->namespace && $this->unshiftTokens(array(T_NS_SEPARATOR, '\\'));
                $this->dependencies['ClassInfo']->removeNsPrefix();

                return false;
            }
        }
        else
        {
            $this->unshiftTokens(array(T_STRING, $c));
            return $this->namespace && $this->unshiftTokens(array(T_NS_SEPARATOR, '\\'));
        }
    }

    protected function tagClass(&$token)
    {
        $this->register(array('tagClassOpen' => T_SCOPE_OPEN));

        if ($this->class->isFinal)
        {
            $a =& $this->types;
            end($a);
            $this->texts[key($a)] = '';
            unset($a[key($a)]);
        }
    }

    protected function tagClassName(&$token)
    {
        $c = $this->class;
        if (T_CLASS !== $c->type) return; // Superpositioning is only for classes, not for traits nor interfaces
        $token[1] .= $c->suffix = '__' . (0 <= $this->level ? $this->level : '00');
        0 <= $this->level && $this->register(array('tagExtendsSelf' => T_USE_CLASS));
        $c->isTop = $this->topClass && 0 === strcasecmp(strtr($this->topClass, '\\', '_'), strtr($c->nsName, '\\', '_'));
    }

    protected function tagExtendsSelf(&$token)
    {
        if (0 === strcasecmp('_' . strtr($this->class->nsName, '\\', '_'), strtr($this->nsResolved, '\\', '_')))
        {
            $this->class->extendsSelf = true;
            $this->class->extends = $this->class->nsName . '__' . ($this->level ? $this->level - 1 : '00');

            $this->dependencies['ClassInfo']->removeNsPrefix();

            $this->unshiftTokens(array(T_STRING, $this->class->extends));
            return $this->namespace && $this->unshiftTokens(array(T_NS_SEPARATOR, '\\'));
        }
    }

    protected function tagClassOpen(&$token)
    {
        $this->unregister(array(
            'tagExtendsSelf' => T_USE_CLASS,
            __FUNCTION__     => T_SCOPE_OPEN,
        ));
        $this->register(array('tagClassClose' => T_BRACKET_CLOSE));
    }

    protected function tagPrivate(&$token)
    {
        // "private static" methods or properties are problematic when considering class superposition.
        // To work around this, we change them to "protected static", and warn about it
        // (except for files in the include path). Side effects exist but should be rare.

        // Look backward and forward for the "static" keyword
        if (T_STATIC !== $this->prevType)
        {
            $t = $this->getNextToken();

            if (T_STATIC !== $t[0]) return;
        }

        $token = array(T_PROTECTED, 'protected');

        if (0 <= $this->level)
        {
            $this->setError("Private statics do not work with class superposition, please use protected statics instead", E_USER_NOTICE);
        }

        return false;
    }

    protected function tagClassClose(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_BRACKET_CLOSE));
        $c = $this->class;
        $a = strtolower(strtr($c->nsName, '\\', '_'));

        if (strpos($c->nsName, '\\') && function_exists('class_alias'))
        {
            $token[1] .= "\\class_alias('{$c->nsName}{$c->suffix}','{$a}{$c->suffix}');";
        }

        $s = '\\Patchwork_Superloader';
        PHP_VERSION_ID < 50300 && $s[0] = ' ';

        if ($c->isFinal || $c->isTop)
        {
            $token[1] = "}"
                . ($c->isFinal ? 'final' : ($c->isAbstract ? 'abstract' : ''))
                . " class {$c->name} extends {$c->name}{$c->suffix} {" . $token[1]
                . "{$s}::\$locations['{$a}']=1;";

            strpos($c->nsName, '\\')
                && function_exists('class_alias')
                && $token[1] .= "\\class_alias('{$c->nsName}','{$a}');";
        }

        if ($c->isAbstract)
        {
            $token[1] .= "{$s}::\$abstracts['{$a}{$c->suffix}']=1;";
        }
    }

    protected function tagRequire(&$token)
    {
        // Every require|include inside files in the include_path
        // is preprocessed thanks to Patchwork_Superloader::getProcessedPath().

        $token['no-autoload-marker'] = true;

        if (Patchwork_Superloader::$turbo
          && $this->dependencies['ConstantExpression']->nextExpressionIsConstant()
          && false !== $a = Patchwork_Superloader::getProcessedPath($this->expressionValue, true))
        {
            $token =& $this->getNextToken();
            $token[1] = ' ' . self::export($a) . str_repeat("\n", substr_count($token[1], "\n"));
        }
        else
        {
            $this->unshiftTokens(
                $this->namespace ? array(T_NS_SEPARATOR, '\\') : array(T_WHITESPACE, ' '),
                array(T_STRING, 'Patchwork_Superloader'), array(T_DOUBLE_COLON, '::'),
                array(T_STRING, 'getProcessedPath'), '('
            );

            $this->exprLevel = -1;
            $this->register($this->exprCallbacks);
        }
    }

    protected function incExprLevel(&$token)
    {
        ++$this->exprLevel;
    }

    protected function decExprLevel(&$token)
    {
        switch ($token[0])
        {
        case ',': if ($this->exprLevel) break;

        case ')':
        case '}':
        case ']':
        case ':': if ($this->exprLevel--) break;

        case ';':
        case T_AS:
        case T_CLOSE_TAG:
            $this->unregister($this->exprCallbacks);
            return $this->unshiftTokens(')', $token);
        }
    }

    protected function tagSpecialFunc(&$token)
    {
        switch (strtolower($this->nsResolved))
        {
        case '\patchworkpath':
            // Append its fourth arg to patchworkPath()
            new Patchwork_PHP_Parser_Bracket_PatchworkPath($this, $this->level);
            break;

        case '\class_parents':
        case '\class_implements':
        case '\class_exists':
        case '\trait_exists':
        case '\interface_exists':
            // Force a lightweight autoload
            new Patchwork_PHP_Parser_Bracket_ClassExists($this);
            break;

        case '\get_class':
            if (empty($this->class)) break;

            $this->getNextToken($i); // eat the next opening bracket
            $t = $this->getNextToken($i);

            if (T_STRING === $t[0] && 0 === strcasecmp('null', $t[1]))
                $t = $this->getNextToken($i);

            if (')' === $t[0])
            {
                $this->dependencies['ClassInfo']->removeNsPrefix();
                while ($this->index < $i) unset($this->tokens[$this->index++]);
                return $this->unshiftTokens(array(T_CONSTANT_ENCAPSED_STRING, "'" . $this->class->nsName . "'"));
            }
            break;

        case '\get_parent_class':
            if (empty($this->class)) break;

            $this->getNextToken($i); // eat the next opening bracket
            $t = $this->getNextToken($i);

            if (')' === $t[0])
            {
                --$i;
                $t = $this->index--;
                while ($t < $i) $this->tokens[$t-1] = $this->tokens[$t++];
                $this->tokens[$i-1] = array(T_CONSTANT_ENCAPSED_STRING, "'" . $this->class->nsName . "'");
            }
            break;
        }
    }
}
