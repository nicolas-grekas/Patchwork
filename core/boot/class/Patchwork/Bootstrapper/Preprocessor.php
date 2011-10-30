<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
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
        new Patchwork_PHP_Parser_StringInfo($p);
        new Patchwork_PHP_Parser_NamespaceInfo($p);
        new Patchwork_PHP_Parser_ScopeInfo($p);
        new Patchwork_PHP_Parser_ConstFuncDisabler($p);
        new Patchwork_PHP_Parser_ConstFuncResolver($p);
        new Patchwork_PHP_Parser_ConstantInliner($p, $file, array());
        new Patchwork_PHP_Parser_ClassInfo($p);

        $this->getOverrides(); // Load active overrides

        new Patchwork_PHP_Parser_FunctionOverriding($p, $this->newOverrides);
        new Patchwork_PHP_Parser_NamespaceResolver($p);
        $p = $this->parser = new Patchwork_PHP_Parser_StaticState($p);

        $code = $p->getRunonceCode($code);

        if ($p = $p->getErrors())
        {
            foreach ($p as $p)
            {
                switch ($p['type'])
                {
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
        return $code;
    }

    function getOverrides()
    {
        $o = $this->newOverrides;
        $this->newOverrides = array();
        return Patchwork_PHP_Parser_FunctionOverriding::loadOverrides($o);
    }
}
