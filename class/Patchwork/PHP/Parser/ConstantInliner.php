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
 * The ConstantInliner parser replaces internal, magic and user specified constants by their value.
 */
class Patchwork_PHP_Parser_ConstantInliner extends Patchwork_PHP_Parser
{
    protected

    $file,
    $dir,
    $newConsts,
    $constants = array(),
    $nextScope = '',
    $callbacks = array(
        'tagNameConst' => T_NAME_CONST,
        'tagScopeOpen' => T_SCOPE_OPEN,
        'tagConstant'  => T_USE_CONSTANT,
        'tagFileC'     => array(T_FILE, T_DIR),
        'tagLineC'     => T_LINE,
        'tagScopeName' => array(T_CLASS, T_TRAIT, T_FUNCTION),
        'tagClassC'    => T_CLASS_C,
        'tagTraitC'    => T_TRAIT_C,
        'tagMethodC'   => T_METHOD_C,
        'tagFuncC'     => T_FUNC_C,
        'tagNsC'       => T_NS_C,
    ),

    $scope, $namespace, $nsResolved,
    $dependencies = array('ScopeInfo' => array('scope', 'namespace', 'nsResolved'));

    protected static $staticConsts = array();


    static function loadConsts($consts)
    {
        foreach ($consts as $c) switch (true)
        {
        case false !== strpos($c, ':'):
        case 'INF' === $c:
        case 'NAN' === $c:
        case !defined($c): break;
        default:
            if (false !== $i = strrpos($c, '\\'))
                $c = strtolower(substr($c, 0, $i - 1)) . substr($c, $i);

            self::$staticConsts[$c] = self::export(constant($c));
        }

        return array_keys(self::$staticConsts);
    }

    function __construct(parent $parent, $file, &$new_consts = array())
    {
        if ($file)
        {
            $this->file = self::export($file);
            $this->dir = self::export(dirname($file));
        }
        else unset($this->callbacks['tagFileC'], $this->callbacks['tagLineC']);

        $file = self::$staticConsts;
        self::loadConsts($new_consts);
        $this->constants = self::$staticConsts;
        self::$staticConsts = $file;
        $this->newConsts =& $new_consts;

        parent::__construct($parent);
    }

    protected function tagNameConst(&$token)
    {
        switch ($this->scope->type)
        {
        case T_OPEN_TAG:
        case T_NAMESPACE:
            $c = strtolower($this->namespace) . $token[1];
            isset(self::$staticConsts[$c]) || $this->newConsts[] = $c;
        }
    }

    protected function tagConstant(&$token)
    {
        switch (strtolower($this->nsResolved)) {case '\true': case '\false': case '\null': return;}

        // Inline constants only if they are fully namespace resolved

        if ('\\' === $this->nsResolved[0])
        {
            $c = strrpos($this->nsResolved, '\\');
            $c = 0 !== $c
                ? strtolower(substr($this->nsResolved, 1, $c)) . substr($this->nsResolved, $c)
                : substr($this->nsResolved, 1);

            if (isset($this->constants[$c]))
            {
                $token[0] = T_CONSTANT_ENCAPSED_STRING;
                $token[1] = $this->constants[$token[1]];
                $this->unshiftTokens($token);

                $this->dependencies['ScopeInfo']->removeNsPrefix();

                return false;
            }
        }
    }

    protected function tagFileC(&$token)
    {
        return $this->unshiftTokens(array(
            T_CONSTANT_ENCAPSED_STRING,
            T_FILE === $token[0] ? $this->file : $this->dir
        ));
    }

    protected function tagLineC(&$token)
    {
        return $this->unshiftTokens(array(T_LNUMBER, $this->line));
    }

    protected function tagScopeName(&$token)
    {
        $t = $this->getNextToken();

        T_STRING === $t[0] && $this->nextScope = $t[1];
    }

    protected function tagScopeOpen(&$token)
    {
        if ($this->scope->parent)
        {
            $this->scope->classC = $this->scope->parent->classC;
            $this->scope->traitC = $this->scope->parent->traitC;
            $this->scope->funcC  = $this->scope->parent->funcC ;

            switch ($this->scope->type)
            {
            case T_CLASS:
                $this->scope->classC = $this->namespace . $this->nextScope;
                break;

            case T_TRAIT:
                $this->scope->traitC = $this->namespace . $this->nextScope;
                break;

            case T_FUNCTION:
                $this->scope->funcC = '' !== $this->nextScope
                    ? ($this->scope->classC ? '' : $this->namespace) . $this->nextScope
                    : ($this->namespace . '{closure}');
                break;
            }
        }
        else
        {
            $this->scope->classC = $this->scope->traitC = $this->scope->funcC  = '';
        }

        $this->nextScope = '';
    }

    protected function tagClassC(&$token)
    {
        return $this->unshiftTokens(array(T_CONSTANT_ENCAPSED_STRING, "'{$this->scope->classC}'"));
    }

    protected function tagTraitC(&$token)
    {
        return $this->unshiftTokens(array(T_CONSTANT_ENCAPSED_STRING, "'{$this->scope->traitC}'"));
    }

    protected function tagMethodC(&$token)
    {
        $c = $this->scope->classC && $this->scope->funcC
            ? "'{$this->scope->classC}::{$this->scope->funcC}'"
            : "''";

        return $this->unshiftTokens(array(T_CONSTANT_ENCAPSED_STRING, $c));
    }

    protected function tagFuncC(&$token)
    {
        return $this->unshiftTokens(array(T_CONSTANT_ENCAPSED_STRING, "'{$this->scope->funcC}'"));
    }

    protected function tagNsC(&$token)
    {
        return $this->unshiftTokens(array(T_CONSTANT_ENCAPSED_STRING, "'" . substr($this->namespace, 0, -1) . "'"));
    }
}
