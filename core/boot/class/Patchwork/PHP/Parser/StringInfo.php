<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

Patchwork_PHP_Parser::createToken('T_NAME_NS', 'T_NAME_CLASS', 'T_NAME_FUNCTION', 'T_NAME_CONST', 'T_USE_NS', 'T_USE_CLASS', 'T_USE_METHOD', 'T_USE_PROPERTY', 'T_USE_FUNCTION', 'T_USE_CONST', 'T_USE_CONSTANT', 'T_GOTO_LABEL', 'T_TYPE_HINT');

/**
 * The StringInfo parser analyses T_STRING tokens and gives them a secondary type to allow more specific semantics.
 *
 * This parser analyses tokens surrounding T_STRING tokens and is able to determine between many different semantics:
 * - T_NAME_NS:       namespace declaration as in namespace FOO\BAR
 * - T_NAME_CLASS:    class, interface or trait declaration as in class FOO {}
 * - T_NAME_FUNCTION: function or method declaration as in function FOO()
 * - T_NAME_CONST:    class or namespaced const declaration as in const FOO
 * - T_USE_NS:        namespace prefix or "use" aliasing as in FOO\bar
 * - T_USE_CLASS:     class usage as in new foo\BAR or FOO::
 * - T_USE_METHOD:    method call as in $a->FOO() or a::BAR()
 * - T_USE_PROPERTY:  property access as in $a->BAR
 * - T_USE_FUNCTION:  function call as in foo\BAR()
 * - T_USE_CONST:     class constant access as in foo::BAR
 * - T_USE_CONSTANT:  global or namespaced constant access as in FOO or foo\BAR
 * - T_GOTO_LABEL:    goto label as in goto FOO or BAR:{}
 * - T_TYPE_HINT:     type hint as in instanceof foo\BAR or function(foo\BAR $a)
 *
 * It also exposes a property and a method:
 * - nsPrefix: non-resolved namespace prefix of the current token
 * - removeNsPrefix(): removes nsPrefix from the output stream for the current token
 */
class Patchwork_PHP_Parser_StringInfo extends Patchwork_PHP_Parser
{
    protected

    $nsPrefix  = '',
    $inConst   = false,
    $inExtends = false,
    $inParam   = 0,
    $inNs      = false,
    $inUse     = false,
    $preNsType = 0,
    $callbacks = array(
        'tagString'   => T_STRING,
        'tagConst'    => T_CONST,
        'tagExtends'  => array(T_EXTENDS, T_IMPLEMENTS),
        'tagFunction' => T_FUNCTION,
        'tagNs'       => T_NAMESPACE,
        'tagUse'      => T_USE,
        'tagNsSep'    => T_NS_SEPARATOR,
    );


    function removeNsPrefix()
    {
        if (empty($this->nsPrefix)) return;

        $t =& $this->types;
        end($t);

        while (null !== $i = key($t)) switch ($t[$i])
        {
            default: break 2;
            case T_STRING: case T_NS_SEPARATOR: case T_NAMESPACE:
                $this->texts[$i] = '';
                unset($t[$i]);
                end($t);
        }

        $this->nsPrefix = '';
        $this->prevType = $this->preNsType;
    }

    protected function tagString(&$token)
    {
        if (T_NS_SEPARATOR !== $p = $this->prevType) $this->nsPrefix = '';

        switch ($p)
        {
        case T_INTERFACE:
        case T_TRAIT:
        case T_CLASS: return T_NAME_CLASS;
        case T_GOTO:  return T_GOTO_LABEL;

        case '&': if (T_FUNCTION !== $this->penuType) break;
        case T_FUNCTION: return T_NAME_FUNCTION;

        case ',':
        case T_CONST:
            if ($this->inConst) return T_NAME_CONST;

        default:
            if ($this->inNs ) return T_NAME_NS;
            if ($this->inUse) return T_USE_NS;
        }

        $n = $this->getNextToken();

        if (T_NS_SEPARATOR === $n = $n[0])
        {
            if (T_NS_SEPARATOR === $p)
            {
                $this->nsPrefix .= $token[1];
            }
            else
            {
                $this->nsPrefix = $token[1];
                $this->preNsType = $p;
            }

            return T_USE_NS;
        }

        switch (empty($this->nsPrefix) ? $p : $this->preNsType)
        {
        case ',': if (!$this->inExtends) break;
        case T_NEW:
        case T_EXTENDS:
        case T_IMPLEMENTS: return T_USE_CLASS;
        case T_INSTANCEOF: return T_TYPE_HINT;
        }

        switch ($n)
        {
        case T_DOUBLE_COLON: return T_USE_CLASS;
        case T_VARIABLE:     return T_TYPE_HINT;

        case '(':
            switch ($p)
            {
            case T_OBJECT_OPERATOR:
            case T_DOUBLE_COLON: return T_USE_METHOD;
            default:             return T_USE_FUNCTION;
            }

        case ':':
            if ('{' === $p || ';' === $p) return T_GOTO_LABEL;
            // No break;

        default:
            switch ($p)
            {
            case T_OBJECT_OPERATOR: return T_USE_PROPERTY;
            case T_DOUBLE_COLON:    return T_USE_CONST;

            case '(':
            case ',':
                if (1 === $this->inParam && '&' === $n) return T_TYPE_HINT;
                // No break;
            }
        }

        return T_USE_CONSTANT;
    }

    protected function tagConst(&$token)
    {
        $this->inConst = true;
        $this->register(array('tagConstEnd' => ';'));
    }

    protected function tagConstEnd(&$token)
    {
        $this->inConst = false;
        $this->unregister(array(__FUNCTION__ => ';'));
    }

    protected function tagExtends(&$token)
    {
        $this->inExtends = true;
        $this->register(array('tagExtendsEnd' => '{'));
    }

    protected function tagExtendsEnd(&$token)
    {
        $this->inExtends = false;
        $this->unregister(array(__FUNCTION__ => '{'));
    }

    protected function tagFunction(&$token)
    {
        $this->register(array(
            'tagParamOpenBracket'  => '(',
            'tagParamCloseBracket' => ')',
        ));
    }

    protected function tagParamOpenBracket(&$token)
    {
        ++$this->inParam;
    }

    protected function tagParamCloseBracket(&$token)
    {
        if (0 >= --$this->inParam)
        {
            $this->inParam = 0;
            $this->unregister(array(
                'tagParamOpenBracket'  => '(',
                'tagParamCloseBracket' => ')',
            ));
        }
    }

    protected function tagNs(&$token)
    {
        $t = $this->getNextToken();

        switch ($t[0])
        {
        case T_STRING:
            $this->inNs = true;
            $this->register(array('tagNsEnd' => array('{', ';')));
            // No break;

        case '{': case ';':
            return T_NAME_NS;

        case T_NS_SEPARATOR:
            return $this->tagString($token);
        }
    }

    protected function tagNsEnd(&$token)
    {
        $this->inNs = false;
        $this->unregister(array(__FUNCTION__ => array('{', ';')));
    }

    protected function tagUse(&$token)
    {
        if (')' !== $this->prevType)
        {
            $this->inUse = true;
            $this->register(array('tagUseEnd' => ';'));
        }
    }

    protected function tagUseEnd(&$token)
    {
        $this->inUse = false;
        $this->unregister(array(__FUNCTION__ => ';'));
    }

    protected function tagNsSep(&$token)
    {
        if (T_STRING === $this->prevType || T_NAMESPACE === $this->prevType)
        {
            $this->nsPrefix .= '\\';
        }
        else
        {
            $this->nsPrefix  = '\\';
            $this->preNsType = $this->prevType;
        }
    }
}
