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


class patchwork_PHP_Parser_marker extends patchwork_PHP_Parser_functionAliasing
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
    $dependencies = array('normalizer', 'classInfo' => array('class', 'scope', 'nsResolved'));


    function __construct(patchwork_PHP_Parser $parent, $inlineClass)
    {
        foreach ($inlineClass as $inlineClass)
            $this->inlineClass[strtolower(strtr($inlineClass, '\\', '_'))] = 1;

        patchwork_PHP_Parser::__construct($parent);
    }

    protected function tagOpenTag(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_SCOPE_OPEN));
        $T = $this->tag;
        $token[1] .= "if(!isset(\$a{$T})){global \$a{$T},\$b{$T},\$c{$T};}isset(\$e{$T})||\$e{$T}=";
        // Add some tokens for the staticState PHP parser
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
            if (T_NS_SEPARATOR === $this->lastType)
            {
                $t =& $this->types;
                end($t);

                do switch (prev($t))
                {
                default: break 2;
                case T_STRING: case T_NS_SEPARATOR:
                    continue 2;
                }
                while (1);

                next($t);
                $t =& $this->texts[key($t)];
            }

        case T_EVAL: $curly = -1; break;
        default:     $curly =  0; break;
        }

        $T = $this->tag;
        $t = "((\$a{$T}=\$b{$T}=\$e{$T})||1?{$t}";
        new patchwork_PHP_Parser_closeMarker($this, $curly);

        0 < $this->scope->markerState || $this->scope->markerState = 1;
    }

    protected function tagScopeOpen(&$token)
    {
        $this->inStatic = false;
        $this->scope->markerState = 0;

        if (T_FUNCTION === $this->scope->type)
        {
            $this->register(array('tagFunctionClose' => T_SCOPE_CLOSE));
        }
        else if (T_CLASS === $this->scope->type)
        {
            $this->inlineClass[strtolower(strtr($this->class->nsName, '\\', '_'))] = 1;
            $this->class->extends && $this->inlineClass[strtolower(strtr($this->class->extends, '\\', '_'))] = 1;
            $this->register(array('tagClassClose' => T_SCOPE_CLOSE));
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

        $token['lastType'] = $this->lastType;
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

        $c = '&' === $this->newToken['lastType'] ? "patchwork_autoload_marker({$c}," : "(({$c})?";

        $this->newToken[1] = $c . $this->newToken[1];

        new patchwork_PHP_Parser_closeMarker($this, $token ? -1 : 0, '&' === $this->newToken['lastType'] ? ')' : ':0)');

        unset($this->newToken['lastType'], $this->newToken);
    }

    protected function tagDoubleColon(&$token)
    {
        if (   $this->inStatic
            || T_STRING !== $this->lastType
            || T_CLASS === $this->scope->type
        ) return;

        $t =& $this->types;
        end($t);

        $c = strtolower(strtr(substr($this->nsResolved, 1), '\\', '_'));
        if (isset($this->inlineClass[$c])) return;

        do switch (prev($t))
        {
        default: break 2;
        case '(': case ',': return; // To not break pass by ref, isset, unset and list
        case T_DEC: case T_INC: case T_STRING: case T_NS_SEPARATOR:
            continue 2;
        }
        while (1);

        $c = $this->getMarker($c);
        $c = '&' === pos($t) ? "patchwork_autoload_marker({$c}," : "(({$c})?";
        $this->scope->markerState || $this->scope->markerState = -1;

        new patchwork_PHP_Parser_closeMarker($this, 0, '&' === pos($t) ? ')' : ':0)');

        next($t);
        $this->texts[key($t)] = $c . $this->texts[key($t)];
    }

    protected function tagFunctionClose(&$token)
    {
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
