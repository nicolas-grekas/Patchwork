<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork\PHP\Parser;

class Patchwork_Bootstrapper_Preprocessor
{
    protected $parser, $newShims;

    function __construct($shims)
    {
        $this->newShims = $shims;
    }

    function staticPass1($code, $file)
    {
        if ('' === $code) return '';

        $p = new Parser\Normalizer;

        PHP_VERSION_ID < 50500 && $p = new Parser\BackportTokens($p);
        PHP_VERSION_ID < 50500 && $p = new Parser\SelfLowerCaser($p);

        new Parser\BracketWatcher($p);
        new Parser\StringInfo($p);
        new Parser\NamespaceInfo($p);
        PHP_VERSION_ID >= 50300 && new Parser\NamespaceBracketer($p);
        new Parser\ScopeInfo($p);
        new Parser\ConstFuncDisabler($p);
        new Parser\ConstFuncResolver($p);
        new Parser\NamespaceResolver($p);

        $this->getShims(); // Load active shims

        new Parser\ConstantInliner($p, $file, $this->newShims[1]);
        new Parser\ClassInfo($p);
        PHP_VERSION_ID < 50500 && new Parser\ClassScalarInliner($p);
        PHP_VERSION_ID < 50300 && new Parser\NamespaceRemover($p);
        new Parser\FunctionShim($p, $this->newShims[0]);
        $p = new Parser\ClosureShim($p);
        $this->parser = new Parser\StaticState($p);
        $this->parser->closureShim = $p;
        $p = $this->parser;

        $code = $p->getRunonceCode($code);

        if ($p = $p->getErrors())
        {
            foreach ($p as $p)
            {
                switch ($p['type'])
                {
                case 0: continue 2;
                case E_USER_NOTICE;
                case E_USER_WARNING;
                case E_USER_DEPRECATED; break;
                default:
                case E_ERROR: $p['type'] = E_USER_ERROR; break;
                case E_NOTICE: $p['type'] = E_USER_NOTICE; break;
                case E_WARNING: $p['type'] = E_USER_WARNING; break;
                case E_DEPRECATED: $p['type'] = E_USER_DEPRECATED; break;
                }

                $code .= "user_error('" . addslashes("{$p['message']} in {$file}:{$p['line']} as parsed by {$p['parser']}") . "', {$p['type']});";
            }
        }

        return $code;
    }

    function staticPass2()
    {
        if (empty($this->parser)) return '';
        $code = substr($this->parser->getRuntimeCode(), 5);
        $code = $this->parser->closureShim->finalizeClosures($code);
        $this->parser = false;

        $o = array();

        foreach ($this->newShims[0] as $replaced => $replacement)
        {
            foreach ($replacement as $tag => $replacement)
            {
                if (false !== strpos($code, $tag))
                {
                    $code = str_replace($tag, '', $code);
                    $o[$replaced] = isset($o[$replaced]) ? $replaced : $replacement;
                }
            }
        }

        $this->newShims[0] = $o;

        return $code;
    }

    function getShims()
    {
        $o = $this->newShims;
        $this->newShims = array(array(), array());
        return array(
            Parser\FunctionShim::loadShims($o[0]),
            Parser\ConstantInliner::loadConsts($o[1]),
        );
    }
}
