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
 * The StaticState parser allows tagging static code inside regular code.
 *
 * This allows PHP to be used as a code preprocessor able to optimise itself
 * by looking at the local setup (PHP version, enabled extensions, etc.)
 */
class Patchwork_PHP_Parser_StaticState extends Patchwork_PHP_Parser
{
    protected

    $bracket = array(),
    $callbacks = array(
        'pushBracket' => array('{', '[', '('),
        'popBracket'  => array('}', ']', ')'),
    ),
    $stateCallbacks = array(
        1 => array(
            'tagEOState1' => array(T_COMMENT, T_WHITESPACE),
        ),
        2 => array(
            'tagEOState2'     => T_COMMENT,
            'tagEOExpression' => array(T_CLOSE_TAG, ';'),
        ),
        3 => array(
            'tagEOState3' => T_COMMENT,
        ),
        4 => array(
            'tagEOState4' => T_COMMENT,
        ),
    ),
    $state = 2,
    $transition = array(),
    $nextState,
    $runtimeKey;

    static $runtimeCode = array();


    function __construct(parent $parent = null)
    {
        parent::__construct($parent);
        $this->register($this->stateCallbacks[2]);
        $this->runtimeKey = mt_rand(1, mt_getrandmax());
    }

    function parse($code)
    {
        $code = $this->getRunonceCode($code);

        $e = error_reporting(error_reporting() | 81);
        set_error_handler(array($this, 'errorHandler'), $e);

        if (false === self::evalbox($code) && $code = error_get_last())
        {
            $this->line = $code['line'];
            $this->setError($code['message'], E_USER_ERROR);
        }

        restore_error_handler();
        error_reporting($e);

        return $this->getRuntimeCode();
    }

    function getRunonceCode($code)
    {
        $this->tokens = $this->getTokens($code);
        $code = $this->parseTokens();
        $var = "$\x9D" . $this->runtimeKey;

        $O = $this->transition ? end($this->transition) : array(1 => 1);
        $O = "{$var}=&" . __CLASS__ . "::\$runtimeCode[{$this->runtimeKey}];{$var}=array(array(1,(";

        $state = 2;
        $o = '';
        $j = 0;

        foreach ($this->transition as $i => $transition)
        {
            do
            {
                $o .= $code[$j];
                unset($code[$j]);
            }
            while (++$j < $i);

            $O .= (2 === $state ? self::export($o) . str_repeat("\n", substr_count($o, "\n")) : $o)
                . (1 !== $state ? ')))' . (3 !== $state ? ';' : '') : '');

            if (1 !== $transition[0])
            {
                $O .= "({$var}[]=array({$transition[1]},"
                    . (4 === $transition[0] ? 'Patchwork_PHP_Parser::export(' : '(');
            }

            $state = $transition[0];
            $o = '';
        }

        $this->transition = array();
        $o = implode('', $code);

        return $O
            . (2 === $state ? self::export($o) . str_repeat("\n", substr_count($o, "\n")) : $o)
            . (1 !== $state ? ')))' . (3 !== $state ? ';' : '') : '')
            . "unset({$var});";
    }

    function getRuntimeCode()
    {
        $code =& self::$runtimeCode[$this->runtimeKey];

        if (empty($code)) return '';

        $line = 1;

        foreach ($code as $k => &$v)
        {
            $v[1] = str_repeat("\n", $v[0] - $line) . $v[1];
            $line += substr_count($v[1], "\n");
            $v = $v[1];
        }

        $code = implode('', $code);
        unset(self::$runtimeCode[$this->runtimeKey]);

        return $code;
    }

    protected function pushBracket(&$token)
    {
        $s = empty($this->nextState) ? $this->state : $this->nextState;

        switch ($token[0])
        {
        case '{': $this->bracket[] = array($s, '}'); break;
        case '[': $this->bracket[] = array($s, ']'); break;
        case '(': $this->bracket[] = array($s, ')'); break;
        }
    }

    protected function popBracket(&$token)
    {
        $s = empty($this->nextState) ? $this->state : $this->nextState;

        if (array($s, $token[0]) !== $last = array_pop($this->bracket))
        {
            if (empty($last) || $token[0] !== $last[1]) $this->transition = array();
            $this->unregister('tagTransition');
            $this->unregister($this->callbacks);
            foreach ($this->stateCallbacks as $s) $this->unregister($s);
        }
    }

    protected function setState($state, &$token = array(0, ''))
    {
        empty($this->nextState) && $this->register('tagTransition');
        $this->nextState = $state;

        if (2 !== $state || 2 < $this->state) $this->tagTransition($token);

        if ($this->state === 2) $this->unregister($this->stateCallbacks[1]);
        if ($this->state === $state) return false;

        $this->unregister($this->stateCallbacks[$this->state]);
        $this->  register($this->stateCallbacks[$state]);

        $this->state = $state;

        return false;
    }

    protected function tagTransition(&$token)
    {
        $this->unregister(__FUNCTION__);
        end($this->texts);
        $this->transition[key($this->texts)+1] = array($this->nextState, $this->line + substr_count($token[1], "\n"));
        unset($this->nextState);
    }

    protected function tagEOState2(&$token)
    {
        if ('/*<*/' === $token[1])
        {
            return $this->setState(4);
        }
        else if ('/**/' === $token[1] && "\n" === substr(end($this->texts), -1))
        {
            return $this->setState(1);
        }
    }

    protected function tagEOExpression(&$token)
    {
        $this->unregister(array(__FUNCTION__ => $this->stateCallbacks[2][__FUNCTION__]));
        $this->  register($this->stateCallbacks[1]);
    }

    protected function tagEOState1(&$token)
    {
        if ('/*<*/' === $token[1]) return $this->setState(3);

        false !== strpos($token[1], "\n")
            && '/*' !== substr($token[1], 0, 2)
            && $this->setState(2, $token);
    }

    protected function tagEOState3(&$token)
    {
        if ('/*>*/' === $token[1]) return $this->setState(1);
    }

    protected function tagEOState4(&$token)
    {
        if ('/*>*/' === $token[1]) return $this->setState(2);
    }

    protected static function evalbox($code)
    {
        return eval('unset($code);' . $code);
    }

    protected function errorHandler($no, $message, $file, $line)
    {
        $this->line = $line;
        $this->setError($message, $no & error_reporting());
    }
}
