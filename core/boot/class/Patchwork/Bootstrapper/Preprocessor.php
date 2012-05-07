<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class Patchwork_Bootstrapper_Preprocessor
{
    protected $parser, $newOverrides;

    function __construct($overrides)
    {
        $this->newOverrides = $overrides;
    }

    function staticPass1($code, $file)
    {
        if ('' === $code) return '';

        $p = new Patchwork_PHP_Parser_Normalizer;

        PHP_VERSION_ID < 50400 && $p = new Patchwork_PHP_Parser_Backport54Tokens($p);
        PHP_VERSION_ID < 50300 && new Patchwork_PHP_Parser_Backport53Tokens($p);

        new Patchwork_PHP_Parser_BracketWatcher($p);
        new Patchwork_PHP_Parser_StringInfo($p);
        new Patchwork_PHP_Parser_NamespaceInfo($p);
        new Patchwork_PHP_Parser_ScopeInfo($p);
        new Patchwork_PHP_Parser_ConstFuncDisabler($p);
        new Patchwork_PHP_Parser_ConstFuncResolver($p);
        new Patchwork_PHP_Parser_NamespaceResolver($p);

        $this->getOverrides(); // Load active overrides

        new Patchwork_PHP_Parser_ConstantInliner($p, $file, $this->newOverrides[1]);
        new Patchwork_PHP_Parser_ClassInfo($p);
        PHP_VERSION_ID < 50300 && new Patchwork_PHP_Parser_NamespaceRemover($p);
        new Patchwork_PHP_Parser_FunctionOverriding($p, $this->newOverrides[0]);
        $p = $this->parser = new Patchwork_PHP_Parser_StaticState($p);

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
        $this->parser = false;

        $o = array();

        foreach ($this->newOverrides[0] as $replaced => $replacement)
        {
            if (1 === count($replacement))
            {
                $o[$replaced] = current($replacement);
            }
            else foreach ($replacement as $tag => $replacement)
            {
                if (false !== strpos($code, $tag))
                {
                    $o[$replaced] = isset($o[$replaced]) ? $replaced : $replacement;
                }
            }
        }

        $this->newOverrides[0] = $o;

        return $code;
    }

    function getOverrides()
    {
        $o = $this->newOverrides;
        $this->newOverrides = array(array(), array());
        return array(
            Patchwork_PHP_Parser_FunctionOverriding::loadOverrides($o[0]),
            Patchwork_PHP_Parser_ConstantInliner::loadConsts($o[1]),
        );
    }
}
