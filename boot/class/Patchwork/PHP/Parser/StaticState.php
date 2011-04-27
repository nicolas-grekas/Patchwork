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

        function_exists('error_get_last') && $e = array(
            @ini_set('error_log',      false),
            @ini_set('display_errors', false),
        );

        set_error_handler(array($this, 'errorHandler'));

        if (false === self::evalbox($code) && isset($e))
        {
            $code = error_get_last();
            $this->line = $code['line'];
            $this->setError($code['message'], E_USER_ERROR);
        }

        restore_error_handler();

        if (isset($e))
        {
            @ini_set('display_errors', $e[1]);
            @ini_set('error_log',      $e[0]);
        }

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
            $this->unregister();

            $last = $last && $s === $last[0] ? ", expecting `{$last[1]}'" : '';

            $this->setError("Syntax error, unexpected `{$token[0]}'{$last}", E_USER_ERROR);
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

    function errorHandler($no, $message, $file, $line)
    {
        $this->line = $line;
        $this->setError($message, $no);
    }
}
