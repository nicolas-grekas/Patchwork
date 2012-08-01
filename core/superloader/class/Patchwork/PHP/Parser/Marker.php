<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class Patchwork_PHP_Parser_Marker extends Patchwork_PHP_Parser_FunctionOverriding
{
    protected

    $tag = "\x9D",
    $newToken,
    $inStatic = false,
    $inlineClass = array('self' => 1, 'parent' => 1, 'static' => 1),
    $callbacks = array(
        'tagOpenTag'     => T_SCOPE_OPEN,
        'tagAutoloader'  => array(T_USE_FUNCTION, T_EVAL, T_REQUIRE_ONCE, T_INCLUDE_ONCE, T_REQUIRE, T_INCLUDE),
        'tagScopeOpen'   => T_SCOPE_OPEN,
        'tagStatic'      => T_STATIC,
        'tagNew'         => T_NEW,
        'tagDoubleColon' => T_DOUBLE_COLON,
    ),

    $class, $scope, $nsResolved,
    $dependencies = array('ClassInfo' => array('class', 'scope', 'nsResolved'));


    function __construct(Patchwork_PHP_Parser $parent, $inlineClass)
    {
        foreach ($inlineClass as $inlineClass)
            $this->inlineClass[strtolower(strtr($inlineClass, '\\', '_'))] = 1;

        Patchwork_PHP_Parser::__construct($parent);
    }

    protected function tagOpenTag(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_SCOPE_OPEN));
        $T = $this->tag;
        $token[1] .= "if(!isset(\$a{$T})){global \$a{$T},\$b{$T},\$c{$T};}isset(\$e{$T})||\$e{$T}=";
        // Add some tokens for the StaticState PHP parser
        $this->unshiftTokens(array(T_COMMENT, '/*<*/'), array(T_WHITESPACE, "\$e{$T}=false"), array(T_COMMENT, '/*>*/'), ';');
    }

    protected function tagAutoloader(&$token)
    {
        if (isset($token['no-autoload-marker'])) return;

        $t =& $token[1];

        switch ($token[0])
        {
        case T_STRING:
            if (!isset(self::$autoloader[strtolower(strtr(substr($this->nsResolved, 1), '\\', '_'))])) return;
            if (T_NS_SEPARATOR === $this->prevType)
            {
                $t =& $this->types;
                end($t);

                for (;;) switch (prev($t))
                {
                default: break 2;
                case T_STRING: case T_NS_SEPARATOR: case T_NAMESPACE:
                    continue 2;
                }

                next($t);
                $t =& $this->texts[key($t)];
            }

        case T_EVAL: $curly = -1; break;
        default:     $curly =  0; break;
        }

        $T = $this->tag;
        new Patchwork_PHP_Parser_CloseMarker($this, $t, $curly, "((\$a{$T}=\$b{$T}=\$e{$T})||1?", ':0)');

        0 < $this->scope->markerState || $this->scope->markerState = 1;
    }

    protected function tagScopeOpen(&$token)
    {
        $this->inStatic = false;
        $this->scope->markerState = 0;

        if (T_FUNCTION === $this->scope->type)
        {
            $this->register(array('tagFunctionClose' => T_BRACKET_CLOSE));
        }
        else if (T_CLASS === $this->scope->type || T_INTERFACE === $this->scope->type || T_TRAIT === $this->scope->type)
        {
            $this->inlineClass[strtolower(strtr($this->class->nsName, '\\', '_'))] = 1;
            $this->class->extends && $this->inlineClass[strtolower(strtr($this->class->extends, '\\', '_'))] = 1;
            $this->register(array('tagClassClose' => T_BRACKET_CLOSE));
        }
    }

    protected function tagStatic(&$token)
    {
        if (T_FUNCTION === $this->scope->type)
        {
            $this->inStatic = true;
            $this->register(array('tagStaticEnd' => ';'));
        }
    }

    protected function tagStaticEnd(&$token)
    {
        $this->inStatic = false;
        $this->unregister(array(__FUNCTION__ => ';'));
    }

    protected function tagNew(&$token)
    {
        $c = $this->getNextToken();

        if (T_WHITESPACE === $c[0]) return;

        $token['prevType'] = $this->prevType;
        $this->newToken =& $token;

        if (T_STRING !== $c[0] && '\\' !== $c[0]) return $this->tagNewClass();

        $this->register(array('tagNewClass' => T_USE_CLASS));
    }

    protected function tagNewClass($token = false)
    {
        if ($token)
        {
            $this->unregister(array(__FUNCTION__ => T_USE_CLASS));

            $c = strtolower(strtr(substr($this->nsResolved, 1), '\\', '_'));
            if (isset($this->inlineClass[$c])) return;
            $c = $this->getMarker($c);
            $this->scope->markerState || $this->scope->markerState = -1;
        }
        else
        {
            $T = $this->tag;
            $c = "\$a{$T}=\$b{$T}=\$e{$T}";
            0 < $this->scope->markerState || $this->scope->markerState = 1;
        }

        new Patchwork_PHP_Parser_CloseMarker(
            $this,
            $this->newToken[1],
            $token ? -1 : 0,
            '&' === $this->newToken['prevType'] ? "patchwork_autoload_marker({$c}," : "(({$c})?",
            '&' === $this->newToken['prevType'] ? ')' : ':0)'
        );

        unset($this->newToken['prevType'], $this->newToken);
    }

    protected function tagDoubleColon(&$token)
    {
        if (   $this->inStatic
            || T_STRING !== $this->prevType
            || T_CLASS === $this->scope->type
            || T_TRAIT === $this->scope->type
        ) return;

        $t =& $this->types;
        end($t);

        $c = strtolower(strtr(substr($this->nsResolved, 1), '\\', '_'));
        if (isset($this->inlineClass[$c])) return;

        for (;;) switch (prev($t))
        {
        default: break 2;
        case '(': case ',': return; // To not break pass by ref, isset, unset and list
        case T_DEC: case T_INC: case T_STRING: case T_NS_SEPARATOR:
            continue 2;
        }

        $this->scope->markerState || $this->scope->markerState = -1;

        $c = $this->getMarker($c);
        $r = '&' === pos($t);
        next($t);

        new Patchwork_PHP_Parser_CloseMarker(
            $this,
            $this->texts[key($t)],
            0,
            $r ? "patchwork_autoload_marker({$c}," : "(({$c})?",
            $r ? ')' : ':0)'
        );
    }

    protected function tagFunctionClose(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_BRACKET_CLOSE));

        if ($this->scope->markerState)
        {
            $T = $this->tag;
            $this->scope->token[1] .= 0 < $this->scope->markerState
                ? "global \$a{$T},\$b{$T},\$c{$T};static \$d{$T}=1;(" . $this->getMarker() . ")&&\$d{$T}&&\$d{$T}=0;"
                : "global \$a{$T},\$c{$T};";
        }
    }

    protected function tagClassClose(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_BRACKET_CLOSE));
        $c = strtolower(strtr($this->class->nsName . (isset($this->class->suffix) ? $this->class->suffix : ''), '\\', '_'));
        $token[1] .= "\$GLOBALS['c{$this->tag}']['{$c}']=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "';";
    }

    protected function getMarker($class = '')
    {
        $T = $this->tag;
        $class = '' !== $class ? "isset(\$c{$T}['{$class}'])||" : "\$e{$T}=\$b{$T}=";
        return $class . "\$a{$T}=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "'";
    }
}
