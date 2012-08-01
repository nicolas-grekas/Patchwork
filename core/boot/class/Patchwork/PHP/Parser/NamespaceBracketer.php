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
 * The NamespaceBracketer parser transformes the regular namespace syntax to the alternate bracketed syntax.
 */
class Patchwork_PHP_Parser_NamespaceBracketer extends Patchwork_PHP_Parser
{
    protected

    $nsClose   = false,
    $callbacks = array(
        'tagOpenTag' => T_OPEN_TAG,
        'tagNs' => T_NAMESPACE,
        'tagEnd' => T_ENDPHP,
    ),
    $dependencies = array('StringInfo', 'Normalizer');


    function __construct(parent $parent)
    {
        if (PHP_VERSION_ID < 50300) $this->callbacks = array();
        parent::__construct($parent);
    }

    protected function tagOpenTag()
    {
        $this->unregister(array(__FUNCTION__ => T_OPEN_TAG));

        $t = $this->getNextToken();

        if (T_NAMESPACE !== $t[0])
            $this->unshiftTokens(array(T_NAMESPACE, 'namespace'), ';');
    }

    protected function tagNs(&$token)
    {
        if (!isset($token[2][T_NAME_NS])) return;

        if ($this->nsClose)
        {
            $this->nsClose = false;
            return $this->unshiftTokens('}', $token);
        }
        else $this->register(array('tagNsEnd' => array('{', ';')));
    }

    protected function tagNsEnd(&$token)
    {
        $this->unregister(array('tagNsEnd' => array('{', ';')));

        if (';' === $token[0])
        {
            $this->nsClose = true;
            return $this->unshiftTokens('{');
        }
    }

    protected function tagEnd(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_ENDPHP));
        return $this->unshiftTokens('}', $token);
    }
}
